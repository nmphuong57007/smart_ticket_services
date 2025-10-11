<?php

namespace App\Http\Validator\User;

use App\Http\Validator\BaseValidator;

class UpdateUserValidator extends BaseValidator
{
    private $userId;

    public function __construct($userId = null)
    {
        $this->userId = $userId;
    }

    /**
     * Get validation rules
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = [
            'fullname' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|required|email|max:100',
            'phone' => 'sometimes|nullable|string|max:20',
            'address' => 'sometimes|nullable|string|max:255',
            'gender' => 'sometimes|nullable|in:male,female,other'
        ];

        // Add unique constraints if userId is provided
        if ($this->userId) {
            $rules['email'] .= '|unique:users,email,' . $this->userId;
            $rules['phone'] .= '|unique:users,phone,' . $this->userId;
        } else {
            $rules['email'] .= '|unique:users,email';
            $rules['phone'] .= '|unique:users,phone';
        }

        return $rules;
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
            'gender.in' => 'Giới tính phải là male, female hoặc other'
        ];
    }

    /**
     * Set user ID for unique validation
     *
     * @param int $userId
     * @return self
     */
    public function setUserId($userId): self
    {
        $this->userId = $userId;
        return $this;
    }
}