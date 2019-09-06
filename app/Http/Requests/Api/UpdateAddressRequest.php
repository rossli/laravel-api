<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'receiver_mobile' => ['required', 'mobile'],
            'receiver_name' => ['required'],
            'province' => ['required'],
            'city' => ['required'],
            'district' => ['required'],
            'address' => ['required'],
        ];
    }

    public function messages()
    {
        return [
            'receiver_mobile.required' => '手机号必填',
            'receiver_mobile.mobile' => '手机号不正确',
            'receiver_name.required' => '收件人必填',
            'province.required' => '省必填',
            'city.required' => '城市必填',
            'district.required' => '行政区必填',
            'address.required' => '详细地址必填',
        ];
    }
}
