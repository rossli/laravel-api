<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\UpdateAddressRequest;
use App\Http\Resources\Api\UserResource;
use App\Models\SmsRecord;
use App\Models\User;
use App\Utils\Utils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends BaseController
{

    public function index(Request $request)
    {
        $user = new UserResource($request->user());
        $data = [
            "id" => $user->id,
            "nick_name" => $user->nick_name,
            "wechat_name" => $user->wechat_name,
            "avatar" => $user->avatar ? config('jkw.cdn_domain') . '/' . $user->avatar : config('jkw.cdn_domain') . '/' . config('jkw.default_avatar'),
            'is_promoter' => $user->is_promoter,
            'url'         => config('jkw.u_index_url') . '/' . Utils::hashids_encode($user->id),
        ];

        return $this->success($data);
    }

    public function updateAvatar(Request $request)
    {

        $avatar = explode(',', $request->avatar);
        if (count($avatar) > 1) {
            $avatar = base64_decode($avatar[1]);
            $filename = 'images/' . date('Y-m-d-h-i-s') . '-' . uniqid() . '.png';
            $bool = Storage::disk('oss')->put($filename, $avatar);
            if ($bool) {
                $request->user()->avatar = $filename;
                $request->user()->save();
                return $this->success('success');
            } else {
                $this->failed('数据错误', -1);
            }
        }
        $this->failed('图片不能为空');
    }

    public function updateName(Request $request)
    {
        $this->validate($request, [
            'nick_name' => 'required|max:100',
        ]);
        $user = new UserResource($request->user());
        if ($user) {
            $user->update([
                'nick_name' => $request->nick_name,
            ]);
        }

        return $this->success('修改成功', '200');
    }

    public function updateSex(Request $request)
    {
        $user = new UserResource($request->user());
        if ($user) {
            $user->update([
                'sex' => $request->sex,
            ]);
        }

        return $this->success('修改成功', '200');
    }

    public function updatePassword(Request $request)
    {
        $this->validate($request, [
            'password' => 'required|min:6|max:16',
        ]);
        $user = new UserResource($request->user());
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

    public function captcha()
    {

        $captcha = app('captcha')->create('default', TRUE);
        Redis::set($captcha['key'], 1, 'EX', 300);

        return $this->success([
            'code' => '200',
            'message' => 'success',
            'ckey' => $captcha['key'],
            'img' => $captcha['img'],
        ]);
    }

    public function smsCode(Request $request)
    {
        info('sms:toSend');
        $data = $request->only(['ckey', 'captcha', 'mobile', 'method']);

        if (!Redis::get($request->input('ckey'))) {
            return $this->failed('验证码错误或验证码超时');
        }

        $validator = Validator::make($data, [
            'ckey' => 'required',
            'captcha' => 'required|captcha_api:' . $request->input('ckey'),
            'mobile' => 'required|mobile',
            'method' => [
                'required',
                Rule::in(['register', 'reset', 'smsLogin','bind']),
            ],
        ], [
            'ckey.required' => 'ckey必填',
            'captcha.required' => '图型验证码必填',
            'captcha.captcha_api' => '图型验证码错误',
            'mobile.required' => '手机号码必填',
            'mobile.mobile' => '手机号码错误',
            'method.required' => 'method 必填',
            'method.in' => 'method 必须是 register,smsLogin ,bind 或  reset',
        ]);

        if ($validator->fails()) {
            return $this->failed($validator->errors()->first());
        }

        Redis::del($request->input('ckey'));

        return $this->smsSend($data['mobile'], $data['method']);

    }

    private function smsSend($mobile, $method)
    {

        if (env('APP_DEBUG')) {
            $code = 1234;
            Redis::set($mobile . '_sms' . $method, $code, 'EX', 300);
            $this->createSmsRecord($mobile, '测试短信验证密码', '测试短信验证密码');

            return $this->success('测试短信发送成功,验证码为1234');
        }

        $remain = Redis::ttl(md5($mobile));
        if ($remain > 1) {
            return $this->failed('请在 ' . $remain . 's 后再次发送');
        }
        //限制 IP
        if (($count_ip = Redis::get(request()->ip())) > 20) {
            if ($count_ip < 999) {
                Redis::set(request()->ip(), 999, 'EX', 86400);
            }

            return $this->failed('您今天发送数量已经达到限制,请联系客服解决');
        }
        //限制 单个手机号
        if (($count_mobile = Redis::get($mobile)) > 6) {
            if ($count_mobile < 999) {
                Redis::set($mobile, 999, 'EX', 3600);
            }

            return $this->failed('此手机号发送数量已经达到限制,请一小时后再来');
        }

        $code = mt_rand(1000, 9999);

        $message = '您的验证码为: ' . $code;
        $res = Utils::sendSms253($mobile, $message);
        if ($res) {
            Redis::set($mobile . '_sms' . $method, $code, 'EX', 300);
            Redis::incr(request()->ip());
            Redis::incr($mobile);
            Redis::set(md5($mobile), 1, 'EX', 60);

            $this->createSmsRecord($mobile, $message, $res);

            return $this->success('验证码发送成功');
        }

        return $this->failed('验证码发送失败');
    }

    private function createSmsRecord($mobile, $message, $res)
    {
        SmsRecord::create([
            'mobile' => $mobile,
            'send_data' => $message,
            'response_data' => json_encode($res),
            'remark' => $message,
        ]);
    }

    public function exists()
    {
        $bool = User::where('mobile', request('mobile', ''))->exists();

        if ($bool) {
            return $this->success('用户已存在');
        }

        return $this->success('用户不存在', -1);
    }


    public function address()
    {
        $user = Request()->user();
        if ($user) {
            $data = [
                'receiver_mobile' => $user->receiver_mobile,
                'receiver_name' => $user->receiver_name,
                'province' => $user->province,
                'city' => $user->city,
                'district' => $user->district,
                'address' => $user->address,
            ];
            return $this->success($data);
        }
        return $this->failed('用户不存在');
    }

    public function updateAddress(UpdateAddressRequest $request)
    {
        $user = Request()->user();
        if ($user) {
            $user->update([
                'receiver_mobile' => $request->receiver_mobile,
                'receiver_name' => $request->receiver_name,
                'province' => $request->province,
                'city' => $request->city,
                'district' => $request->district,
                'address' => $request->address,
            ]);
            return $this->success('提交成功');
        }

        return $this->failed('用户不存在');
    }

    public function loginTime()
    {
        $user = Request()->user();

        if ($user) {
            $user->login_time = now();
            $user->save();
            return $this->success('提交成功');
        }

        return $this->failed('用户不存在');
    }


}
