<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends BaseController
{
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'mobile' => $request->get('mobile'),
            'password' => bcrypt($request->get('password')),
        ]);

        $token = $user->createToken('Laravel Password Grant Client')->accessToken;

        return $this->success([
            'token' => $token,
        ]);
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('mobile', $request->mobile)->first();

        if ($user) {

            if (Hash::check($request->password, $user->password)) {
                $token = $user->createToken('Laravel Password Grant Client')->accessToken;
                $response = ['token' => $token];
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

    public function reset(LoginRequest $request){

        $user = User::where('mobile', $request->mobile)->first();

        if ($user) {
            $user->update([
                'password' => bcrypt($request->get('password')),
            ]);

            $token = $user->createToken('Laravel Password Grant Client')->accessToken;

            return $this->success([
                'token' => $token,
            ]);

        } else {
            $response = '用户不存在';
            return $this->failed($response, 422);
        }

    }

}