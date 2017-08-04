<?php
 
Route::get(config('pages.route') . '/trash', 'Webqom\Pages\PageController@trash');
Route::post(config('pages.route') . '/sort', 'Webqom\Pages\PageController@sort');
Route::resource(config('pages.route'), 'Webqom\Pages\PageController');

?>