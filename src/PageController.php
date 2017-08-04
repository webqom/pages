<?php

namespace Webqom\Pages;

use \App\Http\Requests;
use \App\Http\Controllers\Controller;
use \Webqom\Pages\Page;

use Illuminate\Http\Request;

class PageController extends Controller {

	
	public function __construct() {
    	
    	$this->middleware('web');
    	
    	$this->middleware('auth' , [
    	    'except' => ['index', 'show']
        ]);
	}
	
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{           
		$pages = Page::orderBy('created_at', 'desc')->paginate(config('pages.pagination'));  
		return view('pages::index', [
			'pages' => $pages
		]); 
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
	  return view('pages::create');
	}
	
	public function trash() {    
    	$pages = Page::trash()->orderBy('deleted_at', 'desc')->paginate(config('tags.pagination'));
		return view('pages::index', [
			'pages' => $pages
		]);
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store(Request $request)
	{
		$page = new Page;
		$page->title = $request->input('title');
	    $page->slug = $request->input('slug') ? 
	        str_slug($request->input('slug')) :
	        str_slug($page->title);
	    $page->content = $request->input('content');
	    $page->meta_title = $request->input('meta_title') ?: $page->title;
	    $page->meta_keywords = $request->input('meta_keywords');
	    $page->meta_description = $request->input('meta_description') ?: strip_tags(str_limit($page->content, 200, '...'));
	    $page->image = $request->input('image');
	    $page->view = $request->input('view');
	    $page->publish_at = $request->input('publish_at') ?: NULL;
	    $page->unpublish_at = $request->input('unpublish_at') ?: NULL;
	    
	    //Attributes
	    $attributes = $request->input('attributes');
	    $attributes = array_filter(array_combine($attributes['keys'], $attributes['values']));
	    if (!empty($attributes)) {
            $page->attributes = serialize($attributes);
        }
	    
	    $page->save();
	    
	    $page->tags()->detach();
	    $page->tags()->attach($request->input('tags'));
	    	    
	    //Update dates
	    $page->touch();
	    
	    return redirect()->route(config('pages.route') . '.edit', $page->slug);
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($slug)
	{
		
		$page = Page::with(['tags' => function($query) {
            $query->where('public', '=', '1');
        }]);
        
		if (\Auth::check()) {
    		$page = $page->withTrashed();
        }
        
        $page = $page->where('slug','=', $slug)->first();
		
		//If the page slug exists, show it. Otherwise search for tags
		if ($page) {
    		$view = $page->view ?: 'pages::show';
            return view($view, [
			    'page' => $page
            ]);
        } else {
    		$pages = Page::whereHas('tags', function($query) use ($slug) {
                $query->where('name', '=', $slug);
            })->orderBy('sort', 'ASC')->orderBy('title', 'ASC');
            
            //If we are authorized, show unpublished and trashed items
            if (\Auth::check()) {
                $pages = $pages->withTrashed();
            } else {
                $pages = $pages->published();
            }
            
            $pages = $pages->paginate(config('tags.pagination'));
            
            return view('pages::index', [
			    'pages' => $pages
            ]);
		}
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $slug
	 * @return Response
	 */
	public function edit($slug)
	{
		$page = Page::with('tags')->withTrashed()->where('slug', '=', $slug)->first();
		return view('pages::edit', [
    		'page' => $page
		]);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update(Request $request, $id)
	{
		if ($page = Page::withTrashed()->where('id', '=', $id)->first()) {
    	    $page->title = $request->input('title');
    	    $page->slug = $request->input('slug') ?: str_slug($page->title);
    	    $page->content = $request->input('content');
    	    $page->meta_title = $request->input('meta_title') ?: $page->title;
    	    $page->meta_keywords = $request->input('meta_keywords');
    	    $page->meta_description = $request->input('meta_description') ?: strip_tags(str_limit($page->content, 200, '...'));
    	    $page->image = $request->input('image');
    	    $page->view = $request->input('view');
    	    $page->publish_at = $request->input('publish_at') ?: NULL;
    	    $page->unpublish_at = $request->input('unpublish_at') ?: NULL;
    	    $page->sort = $request->input('sort');
    	    
    	    //Attributes
    	    $attributes = $request->input('attributes');
    	    $attributes = array_filter(array_combine($attributes['keys'], $attributes['values']));
    	    if (!empty($attributes)) {
                $page->attributes = serialize($attributes);
            } else {
                $page->attributes = '';
            }
    	    
    	    $page->update();
    	    
    	    $page->tags()->detach();
    	    $page->tags()->attach($request->input('tags'));
    	    
    	    if ($page->trashed()) {
        	    $page->restore();
    	    }
    	    
    	    //Update dates
    	    $page->touch();
    	    
    	    return redirect()->route(config('pages.route') . '.edit', $page->slug);
		}
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		if ($page = Page::where('id', '=', $id)->first()) {
		    Page::destroy($id);
            return redirect()->route(config('pages.route') . '.edit', $page->slug);
        }
	}
	
	public function sort(Request $request)
	{
    	if ($values = $request->input('sort')) {
        	foreach ($values as $value) {
            	if ($page = Page::where('id', '=', $value['id'])->first()) {
                	$page->sort = $value['sort'];
                	$page->update();
            	}
        	}
    	}
	}

}
