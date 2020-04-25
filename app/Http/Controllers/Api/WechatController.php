<?php

namespace App\Http\Controllers\Api;

use App\Utils\Utils;
use EasyWeChat\Factory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use App\Http\Controllers\Controller;
use App\Models\User;

class WechatController extends BaseController
{

    public function wechatLogin()
    {
        $app = Factory::officialAccount(config('wechat.official_account.default'));

        return $app->oauth->scopes(['snsapi_userinfo'])->redirect();
    }

    public function wechatInfo()
    {
        $app = Factory::officialAccount(config('wechat.official_account.default'));
        $user_info = $app->oauth->user()->toArray(); //获取用户授权信息
        //判断在数据库当中是否存在openid
        $user = User::where('openid', '=', $user_info['original']['openid'])->first();
        if (!$user) {
            //如果user表不存在openid，则为新用户
            $create_data = [
                'register_type' => 2,//2表示注册来源为微信网页登录
                'wechat_name'   => $user_info['original']['nickname'],
                'avatar'        => $user_info['original']['headimgurl'],
                'openid'        => $user_info['original']['openid'],
                'sex'           => $user_info['original']['sex'],
                'province'      => $user_info['original']['province'],
                'city'          => $user_info['original']['city'],
                'login_time'    => now(),
            ];
            $user = User::create($create_data);
        } else {
            //如果微信个人信息有修改的情况，update user表的数据
            $update_data = [
                'wechat_name' => $user_info['original']['nickname'],
                'avatar'      => $user_info['original']['headimgurl'],
                'openid'      => $user_info['original']['openid'],
                'sex'         => $user_info['original']['sex'],
                'province'    => $user_info['original']['province'],
                'city'        => $user_info['original']['city'],
                'login_time'  => now(),
            ];
            User::where('openid', $user_info['original']['openid'])->update($update_data);
        }
        //生成token
        $token = $user->createToken('Laravel Password Grant Client')->accessToken;

        return $this->success([
            'token' => $token,
        ]);
    }

}
