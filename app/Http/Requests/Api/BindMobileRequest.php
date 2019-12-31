<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BindMobileRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'method' => [
                'required',
                Rule::in(['bind']),
            ],
            'mobile' => ['required','mobile','unique:users,binding_mobile'],
            'sms' => ['required','sms_captcha'],
            'openid'=>['required']
        ];
    }

    public function messages()
    {
        return [
            'mobile.required' => '手机号必填',
            'openid.required' => 'openid必填',
            'mobile.mobile' => '手机号不正确',
            'mobile.unique' => '手机号已经绑定其他微信了,请换一个手机号绑定',
            'method.required' => 'method 必填',
            'method.in'     => 'method 必须是 bind',
            'sms.required' => '短信验证码必填',
            'sms.sms_captcha' => '短信验证码不正确',
        ];
    }
}
