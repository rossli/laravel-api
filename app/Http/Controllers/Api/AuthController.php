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
        $from_user_id = $request->get('from_user_id');
        if ($from_user_id) {
            $from_user_id = Utils::hashids_decode($from_user_id);
            $from_user_id = $from_user_id[0];
            $user = User::find($from_user_id);
            if ($user) {
                $user->currency++;
                $user->save();
            }
        }
        $user = User::create([
            'mobile' => $request->get('mobile'),
            'password' => bcrypt($request->get('password')),
            'avatar' => config('jkw.default_avatar'),
            'nick_name' => 'jkw_' . time(),
            'sex' => 0,
            'from_user_id' => $from_user_id ?? 0,
        ]);

        $token = $user->createToken('Laravel Password Grant Client')->accessToken;

        return $this->success([
            'token' => $token,
            'code' => Utils::hashids_encode($user->id)
        ]);
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('mobile', $request->mobile)->first();

        if ($user) {

            if (Hash::check($request->password, $user->password)) {
                $token = $user->createToken('Laravel Password Grant Client')->accessToken;
                $response = ['token' => $token, 'code' => Utils::hashids_encode($user->id)];
                return $this->success($response);
            } else {
                $response = '密码错误';
                return $this->failed($response, 422);
            }

        } else {
            $response = '用户不存在';
            return $this->failed($response, 422);
        }
    }

    public function reset(ResetRequest $request)
    {
        $user = User::where('mobile', $request->mobile)->first();
        if (!$user) {
            $response = '用户不存在';
            return $this->failed($response, 422);
        }

        $user->update([
            'password' => bcrypt($request->get('password')),
        ]);

        $token = $user->createToken('Laravel Password Grant Client')->accessToken;

        return $this->success([
            'token' => $token,
            'code' => Utils::hashids_encode($user->id)
        ]);

    }

    public function smsLogin(SmsLoginRequest $request)
    {

        $user = User::where('mobile', $request->mobile)->first();
        if (!$user) {
            $response = '用户不存在';
            return $this->failed($response, 422);
        }

        $token = $user->createToken('Laravel Password Grant Client')->accessToken;

        return $this->success([
            'token' => $token,
            'code' => Utils::hashids_encode($user->id)
        ]);
    }

    public function wxLogin(Request $request)
    {
        if ($request->openid) {
            $user = User::where('openid', $request->openid)->first();

            $user->login_time = now();
            $user->save();

            $token = $user->createToken('Laravel Password Grant Client')->accessToken;

            return $this->success([
                'token' => $token,
                'code' => Utils::hashids_encode($user->id)
            ]);

        }

        $response = '用户不存在';
        return $this->failed($response, 422);
    }

    public function isBind(Request $request)
    {
        $isBind = false;
        if ($request->openid) {
            $user = User::where('openid', $request->openid)->first();
            if ($user) {
                if ($user->binding_mobile) {
                    $isBind = true;
                }
            }
        }
        $data = [
            'isBind' => $isBind
        ];
        return $this->success($data);
    }

    public function bindMobile(BindMobileRequest $request)
    {
        $user = User::where('openid', $request->openid)->first();
        if (!$user) {
            $from_user_id = $request->get('from_user_id');
            if ($from_user_id) {
                $from_user_id = Utils::hashids_decode($from_user_id);
                $from_user_id = $from_user_id[0];
                $user = User::find($from_user_id);
                if ($user) {
                    $user->currency++;
                    $user->save();
                }
            }
            $user = User::create([
                'openid' => $request->openid,
                'avatar' => config('jkw.default_avatar'),
                'nick_name' => 'jkw_' . time(),
                'sex' => 0,
                'from_user_id' => $from_user_id ?? 0,
            ]);
        }
        $user->binding_mobile = $request->mobile;
        $user->login_time = now();
        $user->save();

        $token = $user->createToken('Laravel Password Grant Client')->accessToken;

        return $this->success([
            'token' => $token,
            'code' => Utils::hashids_encode($user->id)
        ]);
    }

}
