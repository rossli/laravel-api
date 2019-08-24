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
    Route::post('/auth/register', 'AuthController@register')->name('api.auth.register');
    Route::post('/auth/login', 'AuthController@login')->name('api.auth.login');
    Route::post('/auth/reset', 'AuthController@reset')->name('api.auth.reset');

    Route::get('/banners', 'BannerController@index')->name('api.banners.index');
    Route::get('/course/recommend', 'CourseController@recommend')->name('api.course.recommend');
    Route::get('/course/open', 'CourseController@open')->name('api.course.open');
    Route::get('/course/{id}', 'CourseController@show')->name('api.course.show');
    Route::get('/course/list', 'CourseController@list')->name('api.course.list');

    Route::get('/book','BookController@index')->name('api.book.index');
    Route::get('/book/list','BookController@list')->name('api.book.list');
    Route::get('/book/{id}','BookController@show')->name('api.book.show');
    Route::get('/course/task/live','CourseTaskController@live')->name('api.course-task.live');
    Route::get('/course/material/{id}','CourseMaterialController@show')->name('api.course-material.show');
    Route::middleware('auth:api')->group(function () {
        Route::get('/users','UserController@index')->name('api.users.index');
        Route::post('/users/update/name','UserController@updateName')->name('api.users.update.name');
        Route::post('/users/update/password','UserController@updatePassword')->name('api.users.update.password');
        Route::post('/users/update/sex','UserController@updateSex')->name('api.users.update.sex');
        Route::post('/course/join/{id}', 'CourseController@join')->name('api.course.join');

        Route::prefix('me')->group(function (){
            Route::get('/course','MeController@course')->name('api.me.course');
            Route::get('/order','MeController@order')->name('api.me.order');
            Route::get('/is-student/{id}','MeController@isStudent')->name('api.me.isStudent');
        });
    });

});

