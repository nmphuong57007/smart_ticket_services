<?php

namespace App\Http\Validator\Auth;

use App\Http\Validator\BaseValidator;

class RegisterValidator extends BaseValidator
{
    /**
     * Get validation rules
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'fullname' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:users,email',
            'phone' => 'nullable|string|max:20|unique:users,phone',
            'address' => 'nullable|string|max:255',
            'gender' => 'nullable|in:male,female,other',
            'password' => 'required|string|min:8',
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
            'fullname.required' => 'Họ tên không được để trống',
            'fullname.string' => 'Họ tên phải là chuỗi ký tự',
            'fullname.max' => 'Họ tên không được vượt quá 100 ký tự',
            'email.required' => 'Email không được để trống',
            'email.email' => 'Email không đúng định dạng',
            'email.max' => 'Email không được vượt quá 100 ký tự',
            'email.unique' => 'Email này đã được sử dụng',
            'phone.string' => 'Số điện thoại phải là chuỗi ký tự',
            'phone.max' => 'Số điện thoại không được vượt quá 20 ký tự',
            'phone.unique' => 'Số điện thoại này đã được sử dụng',
            'address.string' => 'Địa chỉ phải là chuỗi ký tự',
            'address.max' => 'Địa chỉ không được vượt quá 255 ký tự',
            'gender.in' => 'Giới tính phải là male, female hoặc other',
            'password.required' => 'Mật khẩu không được để trống',
            'password.string' => 'Mật khẩu phải là chuỗi ký tự',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự',
            'device_name.required' => 'Tên thiết bị không được để trống',
            'device_name.string' => 'Tên thiết bị phải là chuỗi ký tự',
            'device_name.max' => 'Tên thiết bị không được vượt quá 255 ký tự'
        ];
    }
}