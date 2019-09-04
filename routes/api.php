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

    Route::get('/user/exists','UserController@exists')->name('api.user.check-mobile');
    //验证码
    Route::get('user/captcha','UserController@captcha')->name('api.user.captcha');
    Route::post('user/sms-code','UserController@smsCode')->name('api.user.sms-code');


    Route::get('/banners', 'BannerController@index')->name('api.banners.index');
    Route::get('/course/list', 'CourseController@list')->name('api.course.list');
    Route::get('/course/recommend', 'CourseController@recommend')->name('api.course.recommend');
    Route::get('/course/open', 'CourseController@open')->name('api.course.open');
    Route::get('/course/{id}', 'CourseController@show')->name('api.course.show');

    Route::get('/book', 'BookController@index')->name('api.book.index');
    Route::get('/book/list', 'BookController@list')->name('api.book.list');
    Route::get('/book/{id}', 'BookController@show')->name('api.book.show');
    Route::get('/course/task/live', 'CourseTaskController@live')->name('api.course-task.live');
    Route::get('/course/task/video', 'CourseTaskController@video')->name('api.course-task.video');
    Route::get('/course/material/{id}', 'CourseMaterialController@show')->name('api.course-material.show');
    Route::middleware('auth:api')->group(function () {
        Route::get('/users', 'UserController@index')->name('api.users.index');
        Route::post('/users/update/name', 'UserController@updateName')->name('api.users.update.name');
        Route::post('/users/update/password', 'UserController@updatePassword')->name('api.users.update.password');
        Route::post('/users/update/sex', 'UserController@updateSex')->name('api.users.update.sex');
        Route::post('/course/join/{id}', 'CourseController@join')->name('api.course.join');

        Route::prefix('me')->group(function () {
            Route::get('/course', 'MeController@course')->name('api.me.course');
            Route::get('/order', 'MeController@order')->name('api.me.order');
            Route::get('/is-student/{id}', 'MeController@isStudent')->name('api.me.isStudent');
        });
        Route::get('/order/book-submit', 'OrderController@bookSubmit')->name('api.order.book-submit');
        Route::get('/order/course-submit', 'OrderController@courseSubmit')->name('api.order.course-submit');
        Route::get('/order/cart-submit', 'OrderController@cartSubmit')->name('api.order.cart-submit');
        Route::get('/order/confirm', 'OrderController@confirm')->name('api.order.confirm');
        Route::get('/order/cancel/{id}', 'OrderController@cancel')->name('api.order.cancel');
        Route::get('/order/{id}', 'OrderController@show')->name('api.order.show');
        Route::post('/cart/store', 'ShoppingCartController@store')->name('api.cart.store');
        Route::get('/cart', 'ShoppingCartController@index')->name('api.cart.index');
        Route::get('/cart/count', 'ShoppingCartController@count')->name('api.cart.count');
        Route::post('/cart/delete', 'ShoppingCartController@delete')->name('api.cart.delete');
        Route::get('payment-wx','OrderController@paymentWx')->name('api.order.payment-wx');
        Route::get('payment-h5','OrderController@paymentH5')->name('api.order.payment-h5');
        Route::post('payment/notify', 'PaymentController@notify')->name('api.payment.notify');
        //查询支付状态
        Route::get('payment/status/{id}', 'PaymentController@status')->name('web.payment.status');

    });

});

