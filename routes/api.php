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
    Route::post('/auth/sms-login', 'AuthController@smsLogin')->name('api.auth.sms-login');

    Route::get('/order/openid', 'OrderController@getOpenid')->name('api.order.openid');

    Route::get('/user/exists', 'UserController@exists')->name('api.user.check-mobile');
    //验证码
    Route::get('/user/captcha', 'UserController@captcha')->name('api.user.captcha');
    Route::post('/user/sms-code', 'UserController@smsCode')->name('api.user.sms-code');

    Route::get('/banners', 'BannerController@index')->name('api.banners.index');
    Route::get('/banners-h5', 'BannerController@indexH5')->name('api.banners.indexH5');
    Route::get('/course/list', 'CourseController@list')->name('api.course.list');
    Route::get('/course/recommend', 'CourseController@recommend')->name('api.course.recommend');
    Route::get('/course/guide', 'CourseController@guide')->name('api.course.guide');
    Route::get('/course/kaobian', 'CourseController@kaobian')->name('api.course.kaobian');
    Route::get('/course/open', 'CourseController@open')->name('api.course.open');
    Route::get('/course/{id}', 'CourseController@show')->name('api.course.show');

    Route::get('/order/wx-share', 'OrderController@wxShare')->name('api.order.wx-share');

    Route::get('/books', 'BookController@index')->name('api.book.index');
    Route::get('/book/list', 'BookController@list')->name('api.book.list');
    Route::get('/books/{id}', 'BookController@show')->name('api.book.show');

    Route::get('/group-good/list', 'GroupGoodsController@list')->name('api.group-goods.list');
    Route::get('/group-goods', 'GroupGoodsController@show')->name('api.group-goods.show');
    Route::get('/course/task/live', 'CourseTaskController@live')->name('api.course-task.live');
    Route::get('/course/task/video', 'CourseTaskController@video')->name('api.course-task.video');
    Route::get('/course/material/{id}', 'CourseMaterialController@show')->name('api.course-material.show');

    Route::get('/groups/share/{id}','GroupStudentController@share')->name('api.group-student.share');

    Route::get('sensitive', 'SensitiveController@index')->name('api.sensitive.index');

    Route::middleware('auth:api')->group(function () {
        Route::get('/users', 'UserController@index')->name('api.users.index');
        Route::post('/users/update/name', 'UserController@updateName')->name('api.users.update.name');
        Route::post('/users/update/password', 'UserController@updatePassword')->name('api.users.update.password');
        Route::post('/users/update/sex', 'UserController@updateSex')->name('api.users.update.sex');
        Route::get('/users/login-time', 'UserController@loginTime')->name('api.users.login-time');
        Route::get('/users/address', 'UserController@address')->name('api.user.address');
        Route::post('/users/update/address', 'UserController@updateAddress')->name('api.user.update.address');
        Route::post('/course/join/{id}', 'CourseController@join')->name('api.course.join');
        Route::get('/course/task/live', 'CourseTaskController@live')->name('api.course-task.live');
        Route::get('/course/task/video', 'CourseTaskController@video')->name('api.course-task.video');
        Route::get('/course/material/{id}', 'CourseMaterialController@show')->name('api.course-material.show');

        Route::get('/group-good/confirm', 'GroupGoodsController@confirm')->name('api.group-goods.confirm');

        Route::prefix('me')->group(function () {
            Route::get('/course', 'MeController@course')->name('api.me.course');
            Route::get('/order', 'MeController@order')->name('api.me.order');
            Route::get('/group', 'MeController@group')->name('api.me.group');
            Route::get('/is-student/{id}', 'MeController@isStudent')->name('api.me.isStudent');
        });

        Route::prefix('order')->group(function () {
            Route::get('/book-submit', 'OrderController@bookSubmit')->name('api.order.book-submit');
            Route::get('/course-submit', 'OrderController@courseSubmit')->name('api.order.course-submit');
            Route::get('/cart-submit', 'OrderController@cartSubmit')->name('api.order.cart-submit');
            Route::get('/group-submit', 'OrderController@groupSubmit')->name('api.order.group-submit');
            Route::get('/confirm', 'OrderController@confirm')->name('api.order.confirm');
            Route::get('/cancel/{id}', 'OrderController@cancel')->name('api.order.cancel');
            Route::get('/payment-wx', 'OrderController@paymentWx')->name('api.order.payment-wx');
            Route::get('/payment-h5', 'OrderController@paymentH5')->name('api.order.payment-h5');


            Route::get('/{id}', 'OrderController@show')->name('api.order.show');


        });

        Route::prefix('cart')->group(function () {

            Route::post('/store', 'ShoppingCartController@store')->name('api.cart.store');
            Route::get('/', 'ShoppingCartController@index')->name('api.cart.index');
            Route::get('/count', 'ShoppingCartController@count')->name('api.cart.count');
            Route::post('/delete', 'ShoppingCartController@delete')->name('api.cart.delete');
        });


    });

});

