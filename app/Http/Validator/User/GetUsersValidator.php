<?php

namespace App\Http\Validator\User;

use App\Http\Validator\BaseValidator;

class GetUsersValidator extends BaseValidator
{
    /**
     * Get validation rules
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'search' => 'nullable|string|max:255',
            'role' => 'nullable|in:customer,staff,admin',  
            'status' => 'nullable|in:active,blocked',
            'sort_by' => 'nullable|in:id,fullname,email,created_at,points',
            'sort_order' => 'nullable|in:asc,desc'
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
            'page.integer' => 'Số trang phải là số nguyên',
            'page.min' => 'Số trang phải lớn hơn 0',
            'per_page.integer' => 'Số bản ghi mỗi trang phải là số nguyên',
            'per_page.min' => 'Số bản ghi mỗi trang phải lớn hơn 0',
            'per_page.max' => 'Số bản ghi mỗi trang không được vượt quá 100',
            'search.string' => 'Từ khóa tìm kiếm phải là chuỗi ký tự',
            'search.max' => 'Từ khóa tìm kiếm không được vượt quá 255 ký tự',
            'role.in' => 'Vai trò phải là một trong: customer, staff, admin',
            'status.in' => 'Trạng thái phải là một trong: active, blocked',
            'sort_by.in' => 'Trường sắp xếp phải là một trong: id, fullname, email, created_at, points',
            'sort_order.in' => 'Hướng sắp xếp phải là asc hoặc desc'
        ];
    }
}