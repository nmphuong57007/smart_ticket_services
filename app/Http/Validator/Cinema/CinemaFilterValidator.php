<?php

namespace App\Http\Validator\Cinema;

use App\Http\Validator\BaseValidator;

class CinemaFilterValidator extends BaseValidator
{
    /**
     * Quy tắc kiểm tra dữ liệu filter rạp chiếu
     */
    public function rules(): array
    {
        return [
            'name'      => 'nullable|string|max:255',
            'address'   => 'nullable|string|max:255',
            'status'    => 'nullable|in:active,inactive',
            'per_page'  => 'nullable|integer|min:1|max:100',
            'sort_by'   => 'nullable|in:id,name,address,status,created_at',
            'sort_order' => 'nullable|in:asc,desc',
        ];
    }

    /**
     * Thông báo lỗi tùy chỉnh
     */
    public function messages(): array
    {
        return [
            'name.string'        => 'Tên rạp phải là chuỗi ký tự',
            'name.max'           => 'Tên rạp không được vượt quá 255 ký tự',
            'address.string'     => 'Địa chỉ rạp phải là chuỗi ký tự',
            'address.max'        => 'Địa chỉ rạp không được vượt quá 255 ký tự',
            'status.in'          => 'Trạng thái phải là một trong: active, inactive',
            'per_page.integer'   => 'Số lượng mỗi trang phải là số nguyên',
            'per_page.min'       => 'Tối thiểu 1 bản ghi mỗi trang',
            'per_page.max'       => 'Tối đa 100 bản ghi mỗi trang',
            'sort_by.in'         => 'Trường sắp xếp chỉ được phép là: id, name, address, status, created_at',
            'sort_order.in'      => 'Thứ tự sắp xếp phải là asc hoặc desc',
        ];
    }
}
