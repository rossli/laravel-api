<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends BaseController
{

    public function index(Request $request)
    {
        $user = $request->user();
        return $this->success(new UserResource($user));
    }

    public function updateName(Request $request)
    {
        $this->validate($request, [
            'nick_name' => 'required|max:100',
        ]);
        $user = User::find($request->id);
        if ($user) {
            $user->update([
                'nick_name' => $request->nick_name
            ]);
        }
        return $this->success('修改成功', '200');
    }

    public function updateSex(Request $request)
    {
        $user = User::find($request->id);
        if ($user) {
            $user->update([
                'sex' => $request->sex
            ]);
        }
        return $this->success('修改成功', '200');
    }

    public function updatePassword(Request $request)
    {
        $this->validate($request, [
            'password' => 'required|min:6|max:16',
        ]);
        $user = User::find($request->id);
        if ($user) {
            if (Hash::check($request->oldPassword, $user->password)) {
                if ($request->password !== $request->confirmPassword) {
                    return $this->success('两次输入的密码不相同,请核实后修改', '-1');
                } else {
                    $user->update([
                        'password' => bcrypt($request->get('password')),
                    ]);
                    $token = $user->createToken('Laravel Password Grant Client')->accessToken;

                    return $this->success([
                        'token' => $token,
                    ]);
                }
            } else {
                return $this->success('原始密码不对,请核实后修改', '-1');
            }
        }
        return $this->success( '用户不存在', -1);
    }

}
