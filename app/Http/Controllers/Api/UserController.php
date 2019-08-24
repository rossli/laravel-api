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
        $user = new UserResource($request->user());
        $data = [
            "id" => $user->id,
            "email" => $user->email,
            "mobile" => $user->mobile,
            "receiver_mobile" => $user->receiver_mobile,
            "receiver_name" => $user->receiver_name,
            "mentee_id" => $user->mentee_id,
            "mentee_name" => $user->mentee_name,
            "mentee_avatar" =>config('jkw.cdn_domain') . '/' . $user->mentee_avatar,
            "name" => $user->name,
            "nick_name" => $user->nick_name,
            "wechat_name" => $user->wechat_name,
            "avatar" =>config('jkw.cdn_domain') . '/' . $user->avatar,
            "email_verified_at" => $user->email_verified_at,
            "agreed" => $user->agreed,
            "login_time" => $user->login_time,
            "login_ip" => $user->login_ip,
            "created_ip" => $user->created_ip,
            "invite_code" => $user->invite_code,
            "from_user_id" => $user->from_user_id,
            "register_type" => $user->register_type,
            "register_way" => $user->register_way,
            "uuid" => $user->uuid,
            "uuid_type" => $user->uuid_type,
            "sex" => $user->sex,
            "sign" => $user->sign,
            "province" => $user->province,
            "city" => $user->city,
            "district" => $user->district,
            "address" => $user->address,
            "created_at" => $user->created_at,
            "updated_at" => $user->updated_at

        ];

        return $this->success($data);
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
        return $this->success('用户不存在', -1);
    }

}
