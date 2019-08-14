<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends BaseFormRequest
{

    public function rules()
    {
        return [
            'mobile' => ['required','mobile','unique:users'],
            'password' => ['required', 'max:16', 'min:6']
        ];
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'mobile.required' => '手机号必填',
            'mobile.unique' => '手机号已存在,您可直接登录',
            'mobile.mobile' => '手机号不正确',
            'password.min' => '密码长度不能小于6个字符',
            'password.max' => '密码长度不能大于16个字符',
            'password.required' => '密码必填',
        ];
    }
}
