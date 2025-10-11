<?php

namespace App\Http\Validator\Auth;

use App\Http\Validator\BaseValidator;

class LoginValidator extends BaseValidator
{
    /**
     * Get validation rules
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'required|string|max:255'
        ];
    }

    /**
     * Get custom error messages
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email không được để trống',
            'email.email' => 'Email không đúng định dạng',
            'password.required' => 'Mật khẩu không được để trống',
            'password.string' => 'Mật khẩu phải là chuỗi ký tự',
            'device_name.required' => 'Tên thiết bị không được để trống',
            'device_name.string' => 'Tên thiết bị phải là chuỗi ký tự',
            'device_name.max' => 'Tên thiết bị không được vượt quá 255 ký tự'
        ];
    }
}