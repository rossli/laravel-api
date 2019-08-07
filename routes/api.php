<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::namespace('Api')->prefix('v1')->middleware([\Barryvdh\Cors\HandleCors::class])->group(function () {
    Route::get('/users','UserController@index')->name('api.users.index');
    Route::get('/banners','BannerController@index')->name('api.banners.index');
    Route::get('/course/recommend','CourseController@recommend')->name('api.course.recommend');
    Route::get('/course/open','CourseController@open')->name('api.course.open');
    Route::get('/course/show/{id}','CourseController@show')->name('api.course.show');

});

//跨域处理
Route::middleware([\Barryvdh\Cors\HandleCors::class])->group(function () {

});
