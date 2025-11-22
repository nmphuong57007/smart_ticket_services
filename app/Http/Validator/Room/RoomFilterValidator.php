<?php

namespace App\Http\Validator\Room;

use App\Http\Validator\BaseValidator;

class RoomFilterValidator extends BaseValidator
{
    public function rules(): array
    {
        return [
            'page'        => 'nullable|integer|min:1',
            'per_page'    => 'nullable|integer|min:1|max:100',
            'search'      => 'nullable|string|max:255',
            'status'      => 'nullable|in:active,maintenance,closed',
            'sort_by'     => 'nullable|in:id,name,total_seats,status,created_at',
            'sort_order'  => 'nullable|in:asc,desc',
        ];
    }

    public function messages(): array
    {
        return [
            'page.integer'        => 'Trang phải là số nguyên hợp lệ.',
            'per_page.integer'    => 'Số lượng mỗi trang phải là số nguyên.',
            'per_page.max'        => 'Không được lấy quá 100 bản ghi mỗi trang.',
            'search.string'       => 'Từ khóa tìm kiếm phải là chuỗi ký tự.',
            'status.in'           => 'Trạng thái phòng không hợp lệ.',
            'sort_by.in'          => 'Trường sắp xếp không hợp lệ.',
            'sort_order.in'       => 'Thứ tự sắp xếp chỉ được là tăng (asc) hoặc giảm (desc).',
        ];
    }

    public function filters(): array
    {
        return [
            'search' => 'trim|strip_tags',
        ];
    }
}
