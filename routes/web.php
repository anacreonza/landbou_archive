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

Route::get("/", "SearchController@start");
Route::get('/search', 'SearchController@search')->name('search');
Route::get('/search_options', function(){
    $search_options = Session::get('search_options');
    return view('search_options')->with('search_options', $search_options);
});
Route::get('/results', function(){
    return view('results');
});
Route::get('/admin', function(){
    if (Auth::user()->role != "admin"){
        return redirect("/");
    }
    $indexinfo = app('App\Http\Controllers\IndexController')->get_total_indexed_items();
    return view('admin')->with('indexinfo', $indexinfo);
});
Route::get('/phpadmin', function(){
    return view('phpinfo');
});
Route::get('/admin/delete_index', 'IndexController@delete');
Route::get('/admin/rebuild_index', 'IndexController@rebuild_index');
Route::get('/admin/ingest_files', 'IndexController@ingest_files');

Route::get('/home', 'HomeController@index')->name('home');
Route::get('/logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');

Route::get('/article/read/{id}', 'SearchController@search_id')->name('search_id');
Route::get('/article/download/{id}', 'FileController@download');
Route::get('/file/convert/{id}', 'FileController@convert');
Route::post('/article/create', 'ArticleController@create');
Route::get('/article/edit/{id}', 'ArticleController@edit');
Route::post('/article/update/{id}', 'ArticleController@update');
Route::post('/article/delete/{id}', 'ArticleController@delete');
Route::get('/article/compose/', 'ArticleController@compose');
Auth::routes();

Route::get('/logout', function(){
    return redirect("/login");
});
