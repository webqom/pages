<?php
    
namespace Webqom\Pages;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model {

    use SoftDeletes;
    
	protected $table = 'pages';
	protected $dates = ['deleted_at'];
	
	
	/**
	 * Tag polymorphic relationship
	 * 
	 * @access public
	 * @return \Illuminate\Database\Eloquent\Relations/MorphToMany
	 */
	public function tags() {
    	return $this->morphToMany('\Webqom\Tags\Tag', 'taggable');
	}
	
	
	/**
	 * Scope query to published pages
	 * 
	 * @access public
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function scopePublished($query) {
    	return $query->where('publish_at', '<=', 'CURRENT_TIMESTAMP')
    	    ->where(function($_query) {
        	    $_query->where('unpublish_at', '>', 'CURRENT_TIMESTAMP')
        	        ->orWhereNull('unpublish_at');
    	    });
	}
	
	/**
	 * Scope query to trashed pages
	 * 
	 * @access public
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function scopeTrash($query) {
    	return $query->withTrashed()->whereNotNull('deleted_at');
	}

}
