<?php

namespace App\Http\Requests\Api;


class UserRequest extends BaseFormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        switch (strtoupper($this->method())) {
            case 'PATCH':
                $rules = [
                    'id' => ['required,exists:shop_user,id']
                ];
                break;
            case 'POST':
                $rules = [
                    'name' => ['required', 'max:12', 'unique:users,name'],
                    'password' => ['required', 'max:16', 'min:6']
                ];
                break;
            case 'PUT':
                break;
            case 'DELETE':
                $rules = [
                    'id' => 'required',
                ];
                break;
            default:
                $rules = [];
                break;

        }

        return $rules;
    }

    public function messages()
    {
        return [
            'id.required'=>'用户ID必须填写',
            'id.exists'=>'用户不存在',
            'name.unique' => '用户名已经存在',
            'name.required' => '用户名不能为空',
            'name.max' => '用户名最大长度为12个字符',
            'password.required' => '密码不能为空',
            'password.max' => '密码长度不能超过16个字符',
            'password.min' => '密码长度不能小于6个字符'
        ];
    }
}
