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
    return view('welcome');
});

// Route::get('/sitemap', function()
// {
//    return Response::view('sitemap')->header('Content-Type', 'application/xml');
// });


Route::get('/sitemap'   , 'App\Http\Controllers\SiteMapController@site_map');

//Clear Application Cache: 
Route::get('/clear-cache', function() { 
    $exitCode = Artisan::call('cache:clear');
    return'<h1>Cache facade value cleared</h1>';
});

//Clear all cache: 
Route::get('/optimize', function() {
    $exitCode = Artisan::call('optimize');
    return'<h1>All cache cleared</h1>';
});


//Route cache:
Route::get('/route-clear', function() {
    $exitCode = Artisan::call('route:cache');
    return'<h1>Routes cached</h1>';
});


//Clear Route cache:
Route::get('/route-clear', function() {
    $exitCode = Artisan::call('route:clear');
    return'<h1>Route cache cleared</h1>';
});


//Clear View cache:
Route::get('/view-clear', function() {
    $exitCode = Artisan::call('view:clear');
    return'<h1>View cache cleared</h1>';
});


//Clear Config cache:
Route::get('/config-cache', function() {
    $exitCode = Artisan::call('config:cache');
    return'<h1>Clear Config cleared</h1>';
});