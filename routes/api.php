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
    //注册用户
    Route::post('/auth/register','AuthController@register')->name('api.auth.register');
    Route::post('/auth/login','AuthController@login')->name('api.auth.login');
    Route::post('/auth/reset','AuthController@reset')->name('api.auth.reset');


    Route::get('/banners','BannerController@index')->name('api.banners.index');
    Route::get('/course/recommend','CourseController@recommend')->name('api.course.recommend');
    Route::get('/course/open','CourseController@open')->name('api.course.open');
    Route::get('/course/show','CourseController@show')->name('api.course.show');
    Route::get('/course/list','CourseController@list')->name('api.course.list');

    Route::get('/book','BookController@index')->name('api.book.index');
    Route::get('/book/list','BookController@list')->name('api.book.list');
    Route::get('/book/show','BookController@show')->name('api.book.show');
    Route::get('/course/task/live','CourseTaskController@live')->name('api.course-task.live');
    Route::get('/course/material/show','CourseMaterialController@show')->name('api.course-material.show');


});

Route::namespace('Api')->prefix('v1')->middleware('auth:api')->group(function () {
    Route::get('/users','UserController@index')->name('api.users.index');
});

//跨域处理
Route::middleware([\Barryvdh\Cors\HandleCors::class])->group(function () {

});
