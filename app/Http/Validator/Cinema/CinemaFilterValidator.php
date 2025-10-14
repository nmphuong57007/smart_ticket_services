<?php

namespace App\Http\Validator\Cinema;

use App\Http\Validator\BaseValidator;

class CinemaFilterValidator extends BaseValidator
{
    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'Tên rạp phải là chuỗi ký tự',
            'address.string' => 'Địa chỉ rạp phải là chuỗi ký tự',
            'per_page.integer' => 'Số lượng mỗi trang phải là số nguyên',
            'per_page.min' => 'Tối thiểu 1 bản ghi mỗi trang',
            'per_page.max' => 'Tối đa 100 bản ghi mỗi trang',
        ];
    }
}
