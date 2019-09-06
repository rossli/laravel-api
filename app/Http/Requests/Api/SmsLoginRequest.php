<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SmsLoginRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'mobile' => ['required', 'mobile'],
            'sms' => ['required', 'sms_captcha'],
            'method' => [
                'required',
                Rule::in(['smsLogin']),
            ],
        ];
    }

    public function messages()
    {
        return [
            'mobile.required' => '手机号必填',
            'mobile.mobile' => '手机号不正确',
            'sms.required' => '短信验证码必填',
            'sms.sms_captcha' => '短信验证码不正确',
            'method.required' => 'method必填',
            'method.in' => 'method 必须是 smsLogin',
        ];
    }
}
