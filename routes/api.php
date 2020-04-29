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

Route::namespace('Api')->prefix('v1')->middleware([\Barryvdh\Cors\HandleCors::class])->group(static function () {
    //注册用户
    Route::post('/auth/register', 'AuthController@register')->name('api.auth.register');
    Route::post('/auth/login', 'AuthController@login')->name('api.auth.login');
    Route::post('/auth/reset', 'AuthController@reset')->name('api.auth.reset');
    Route::post('/auth/sms-login', 'AuthController@smsLogin')->name('api.auth.sms-login');
    Route::post('/auth/wx-login', 'AuthController@wxLogin')->name('api.auth.wx-login');
    Route::get('/auth/is-bind', 'AuthController@isBind')->name('api.auth.is-bind');
    Route::post('/auth/bind-mobile', 'AuthController@bindMobile')->name('api.auth.bind-mobile');

    Route::get('/user/exists', 'UserController@exists')->name('api.user.check-mobile');
    //验证码
    Route::get('/user/captcha', 'UserController@captcha')->name('api.user.captcha');
    Route::post('/user/sms-code', 'UserController@smsCode')->name('api.user.sms-code');
    //微信登录
    Route::get('wechat-login', 'WechatController@wechatLogin')->name('api.wechat.wechatLogin');
    Route::get('wechat-info', 'WechatController@wechatInfo')->name('api.wechat.wechatInfo');

    Route::get('/order/openid', 'OrderController@getOpenid')->name('api.order.openid');

    Route::get('/banners', 'BannerController@index')->name('api.banners.index');
    Route::get('/banners-h5', 'BannerController@indexH5')->name('api.banners.indexH5');
    Route::get('/course/list', 'CourseController@list')->name('api.course.list');
    Route::get('/course/recommend', 'CourseController@recommend')->name('api.course.recommend');
    Route::get('/course/guide', 'CourseController@guide')->name('api.course.guide');
    Route::get('/course/kaobian', 'CourseController@kaobian')->name('api.course.kaobian');
    Route::get('/course/open', 'CourseController@open')->name('api.course.open');
    Route::get('/course/{id}', 'CourseController@show')->name('api.course.show');
    Route::get('/courses-type/{project?}', 'CourseController@courseType')->name('api.course.course-type');

    Route::get('/order/wx-share', 'OrderController@wxShare')->name('api.order.wx-share');

    Route::get('/books', 'BookController@index')->name('api.book.index');
    Route::get('/book/list', 'BookController@list')->name('api.book.list');
    Route::get('/books/{id}', 'BookController@show')->name('api.book.show');

    Route::get('/group-good/list', 'GroupGoodsController@list')->name('api.group-goods.list');
    Route::get('/group-goods', 'GroupGoodsController@show')->name('api.group-goods.show');

    Route::get('/course/task/open-course', 'CourseTaskController@openCourse')->name('api.course-task.open-course');

    Route::get('/course/material/{id}', 'CourseMaterialController@show')->name('api.course-material.show');

    Route::get('/groups/share/{id}', 'GroupStudentController@share')->name('api.group-student.share');

    Route::get('sensitive', 'SensitiveController@index')->name('api.sensitive.index');
    //邀请微信二维码
    Route::get('qrcode/{id}', 'QrcodeController@show')->name('api.qrcode.show');
    //意见反馈
    Route::get('suggest', 'SuggestController@index')->name('api.suggest.index');
    Route::post('suggest/create', 'SuggestController@create')->name('api.suggest.create');

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
            Route::get('/is-student/{id}', 'MeController@isStudent')->name('api.me.is-student');
            Route::get('/from-user', 'MeController@fromUser')->name('api.me.from-user');
            Route::get('/currency', 'MeController@currency')->name('api.me.currency');
            Route::get('/code', 'MeController@code')->name('api.me.code');

            //加入分销
            Route::post('/join-promote', 'MeController@joinPromote')->name('api.me.join-promote');

            //分销账户
            Route::get('/account', 'MeController@account')->name('api.me.account');
            //分销账户记录
            Route::get('/account-records', 'MeController@accountRecord')->name('api.me.account-records');

            //分销订单记录
            Route::get('/promote-orders', 'MeController@promoteOrders')->name('api.me.promote-orders');



        });

        Route::prefix('order')->group(function () {
            Route::get('/check-address', 'OrderController@checkAddress')->name('api.order.check-address');

            Route::post('/book-submit', 'OrderController@bookSubmit')->name('api.order.book-submit');
            Route::post('/course-submit', 'OrderController@courseSubmit')->name('api.orde.course-submit');
            Route::post('/cart-submit', 'OrderController@cartSubmit')->name('api.order.cart-submit');
            Route::post('/group-submit', 'OrderController@groupSubmit')->name('api.order.group-submit');

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
        Route::prefix('comment')->group(function () {  //评论相关
            Route::post('store', 'CommentController@store')->name('api.comment.store');
            Route::post('like/store', 'LikeController@store')->name('api.comment-like.store');
        });
        //该课程下登录状态下的列表
        Route::get('/courses/my-comments', 'CourseController@myComments')
            ->name('api.courses.my-comments')
            ->middleware('auth:api');

        //分销课程 列表
        Route::get('/courses/promote-list', 'CourseController@promoteList')->name('api.courses.promote-list');
    });
    Route::prefix('comment')->group(function () {  //评论相关
        Route::post('store', 'CommentController@store')->name('api.comment.store')->middleware('auth:api');
        Route::post('like/store', 'LikeController@store')->name('api.comment-like.store')->middleware('auth:api');
    });

    //该课程下的评论列表
    Route::get('/courses/comments', 'CourseController@comments')->name('api.courses.comments');


});

