<?php

namespace App\Http\Validator\Showtime;

use App\Http\Validator\BaseValidator;

class ShowtimeFilterValidator extends BaseValidator
{
    public function rules(): array
    {
        return [
            'cinema_id' => 'nullable|integer|exists:cinemas,id', // thêm lọc theo rạp
            'room_id' => 'nullable|integer|exists:rooms,id',
            'movie_id' => 'nullable|integer|exists:movies,id',
            'show_date' => 'nullable|date_format:Y-m-d',
            'from_date' => 'nullable|date_format:Y-m-d',
            'to_date' => 'nullable|date_format:Y-m-d|after_or_equal:from_date',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort_by' => 'nullable|in:id,show_date,show_time,created_at',
            'sort_order' => 'nullable|in:asc,desc',
        ];
    }

    public function messages(): array
    {
        return [
            'cinema_id.integer' => 'ID rạp phải là số nguyên',
            'cinema_id.exists' => 'Rạp không tồn tại',

            'room_id.integer' => 'ID phòng phải là số nguyên',
            'room_id.exists' => 'Phòng không tồn tại',

            'movie_id.integer' => 'ID phim phải là số nguyên',
            'movie_id.exists' => 'Phim không tồn tại',

            'show_date.date_format' => 'Ngày chiếu phải có định dạng Y-m-d',
            'from_date.date_format' => 'Ngày bắt đầu phải có định dạng Y-m-d',
            'to_date.date_format' => 'Ngày kết thúc phải có định dạng Y-m-d',
            'to_date.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu',

            'per_page.integer' => 'Số item per page phải là số nguyên',
            'per_page.min' => 'Số item per page tối thiểu là 1',
            'per_page.max' => 'Số item per page tối đa là 100',

            'sort_by.in' => 'Trường sắp xếp không hợp lệ',
            'sort_order.in' => 'Thứ tự sắp xếp phải là asc hoặc desc',
        ];
    }
}
