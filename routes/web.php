<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('search');
});
Route::get('/search', 'SearchController@search')->name('search');
Route::get('/search_options', function(){
    $search_options = Session::get('search_options');
    return view('search_options')->with('search_options', $search_options);
});
Route::get('/results', function(){
    return view('results');
});
Route::get('/admin', function(){
    return view('admin');
});
Route::get('/phpadmin', function(){
    return view('phpinfo');
});
Route::get('/admin/delete_index', 'IndexController@delete');
Route::get('/admin/rebuild_index', 'IndexController@rebuild_index');
Route::get('/view_article', 'SearchController@search_id')->name('search_id');

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
