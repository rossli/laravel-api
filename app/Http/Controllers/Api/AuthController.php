<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\BindMobileRequest;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\ResetRequest;
use App\Http\Requests\Api\SmsLoginRequest;
use App\Models\User;
use App\Utils\Utils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends BaseController
{

    public function register(RegisterRequest $request)
    {
        $userExist = User::where('binding_mobile', $request->get('mobile'))->first();
        if (isset($userExist)) {
            return $this->failed('该手机号已经绑定微信,请使用微信登录');
        }

        $from_user_id = Utils::hashids_decode($request->get('from_user_id'));
        if ($from_user_id!==[]) {
            User::find($from_user_id[0])->increment('currency');
        }
        $user = User::create([
            'mobile' => $request->get('mobile'),
            'binding_mobile' => $request->get('mobile'),
            'password' => bcrypt($request->get('password')),
            'avatar' => config('jkw.default_avatar'),
            'nick_name' => 'jkw_' . time(),
            'sex' => 0,
            'from_user_id' => $from_user_id!==[]?:0,
            'login_time'   => now(),
        ]);

        $token = $user->createToken('Laravel Password Grant Client')->accessToken;

        return $this->success([
            'token' => $token,
            'code' => Utils::hashids_encode($user->id),
        ]);
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('mobile', $request->mobile)->first();

        if ($user) {
            $user->login_time = now();
            $user->save();
            if (Hash::check($request->password, $user->password)) {
                $token = $user->createToken('Laravel Password Grant Client')->accessToken;
                $response = [
                    'token' => $token,
                    'code' => $user->getHashCode(),
                    'is_promoter' => $user->is_promoter,
                    'url' => config('jkw.u_index_url') . '/' . Utils::hashids_encode($user->id),
                ];

                return $this->success($response);
            }

            $response = '密码错误';

            return $this->failed($response, 422);

        }
        $response = '用户不存在';

        return $this->failed($response, 422);
    }

    public function reset(ResetRequest $request)
    {
        $user = User::where('mobile', $request->mobile)->first();
        if (!$user) {
            $response = '用户不存在';

            return $this->failed($response, 422);
        }

        $user->update([
            'password'   => bcrypt($request->get('password')),
            'login_time' => now(),
        ]);

        $token = $user->createToken('Laravel Password Grant Client')->accessToken;

        return $this->success([
            'token'       => $token,
            'code'        => Utils::hashids_encode($user->id),
            'is_promoter' => $user->is_promoter,
        ]);

    }

    public function smsLogin(SmsLoginRequest $request)
    {

        $user = User::where('mobile', $request->mobile)->first();
        if (!$user) {
            $response = '用户不存在';

            return $this->failed($response, 422);
        }
        $user->login_time = now();
        $user->save();
        $token = $user->createToken('Laravel Password Grant Client')->accessToken;

        return $this->success([
            'token'       => $token,
            'code'        => Utils::hashids_encode($user->id),
            'is_promoter' => $user->is_promoter,
        ]);
    }

    public function wxLogin(Request $request)
    {
        if ($request->openid) {
            $user = User::where('openid', $request->openid)->first();
            if(isset($user)){
                if($user->binding_mobile){
                    $user->login_time = now();
                    $user->save();

                    $token = $user->createToken('Laravel Password Grant Client')->accessToken;

                    return $this->success([
                        'token'       => $token,
                        'code'        => Utils::hashids_encode($user->id),
                        'is_promoter' => $user->is_promoter,
                    ]);
                }
            }
            $response = '用户不存在';

            return $this->failed($response, 422);
        }
return $this->failed('数据错误');

    }

    public function isBind(Request $request)
    {
        $isBind = FALSE;
        if ($request->openid) {
            $user = User::where('openid', $request->openid)->first();
            if ($user) {
                if ($user->binding_mobile) {
                    $isBind = TRUE;
                }
            }
        }
        $data = [
            'isBind' => $isBind,
        ];

        return $this->success($data);
    }

    public function bindMobile(BindMobileRequest $request)
    {
        $isMobile = User::where('mobile', $request->mobile)->first();
        if (!$isMobile) {
            $user = User::where('openid', $request->openid)->first();
            if (!$user) {
                $from_user_id = Utils::hashids_decode($request->get('from_user_id'));
                if (count($from_user_id)) {
                    User::find($from_user_id[0])->increment('currency');
                }
                $user = User::create([
                    'openid' => $request->openid,
                    'avatar' => config('jkw.default_avatar'),
                    'nick_name' => 'jkw_' . time(),
                    'sex' => 0,
                    'from_user_id' => $from_user_id!==[] ?:0,
                ]);
            }
            $user->binding_mobile = $request->mobile;
            $user->mobile = $request->mobile;
            $user->password = bcrypt($request->get(substr($request->mobile,-6)+0));
            $user->login_time = now();
            $user->save();

            $token = $user->createToken('Laravel Password Grant Client')->accessToken;

            return $this->success([
                'token' => $token,
                'code' => Utils::hashids_encode($user->id),
            ]);
        }
        return $this->failed('该号码已经有课程,请使用此号码进行登录!');
    }

}
