<?php

namespace App\Providers;

use App\Console\Commands\ModelMakeCommand;
use App\Utils\Utils;
use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\AuthCode;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Laravel\Passport\PersonalAccessClient;
use Laravel\Passport\Token;

class AppServiceProvider extends ServiceProvider
{

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->extend('command.model.make', function ($command, $app) {
            return new ModelMakeCommand($app['files']);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Validator::extend('mobile', function ($attribute, $value, $parameters, $validator) {
            return preg_match("/^1[0-9]{2}[0-9]{8}$|15[0189]{1}[0-9]{8}$|189[0-9]{8}$/", $value);
        });

        Validator::extend('id_card', function ($attribute, $value, $parameters, $validator) {
            return Utils::checkIdCard($value);
        });

        Validator::extend('sms_captcha', function ($attribute, $value, $parameters, $validator) {

            $mobile = request('mobile');
            if ($mobile && Redis::get($mobile . '_sms') == $value) {
                return TRUE;
            }

            return FALSE;
        });

        Passport::routes();

        Passport::tokensExpireIn(now()->addDays(15));

        Passport::refreshTokensExpireIn(now()->addDays(30));

        Passport::personalAccessTokensExpireIn(now()->addMonths(6));

        Passport::useTokenModel(Token::class);
        Passport::useClientModel(Client::class);
        Passport::useAuthCodeModel(AuthCode::class);
        Passport::usePersonalAccessClientModel(PersonalAccessClient::class);


    }
}
