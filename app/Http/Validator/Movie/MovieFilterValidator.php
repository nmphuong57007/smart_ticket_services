<?php

namespace App\Http\Validator\Movie;

use App\Http\Validator\BaseValidator;
use App\Models\Movie;

class MovieFilterValidator extends BaseValidator
{
    /**
     * Get validation rules
     *
     * @return array
     */
    public function rules(): array
    {
        return [

            'page'       => 'nullable|integer|min:1',
            'per_page'   => 'nullable|integer|min:1|max:100',
            'search'     => 'nullable|string|max:255',

            'status'     => 'nullable|in:coming,showing,stopped',
            'genre_id'   => 'nullable|integer|exists:genres,id',
            'genre_slug' => 'nullable|string|exists:genres,slug',

            // THÊM RULE LỌC THEO NGÔN NGỮ (FULL LABEL)
            'language' => 'nullable|string|in:Tiếng Việt,Tiếng Anh,Tiếng Hàn,Tiếng Nhật,Tiếng Trung',

            'sort_by'    => 'nullable|in:id,title,release_date,duration,created_at,status,genre,format,language',
            'sort_order' => 'nullable|in:asc,desc',
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

            'page.integer'     => 'Số trang phải là số nguyên',
            'page.min'         => 'Số trang phải lớn hơn 0',

            'per_page.integer' => 'Số bản ghi mỗi trang phải là số nguyên',
            'per_page.min'     => 'Số bản ghi mỗi trang phải lớn hơn 0',
            'per_page.max'     => 'Số bản ghi mỗi trang không được vượt quá 100',

            'search.string'    => 'Từ khóa tìm kiếm phải là chuỗi ký tự',
            'search.max'       => 'Từ khóa tìm kiếm không được vượt quá 255 ký tự',

            'genre_id.integer' => 'Thể loại phải là số nguyên',
            'genre_id.exists'  => 'Thể loại không tồn tại',

            'status.in'        => 'Trạng thái phim phải là một trong: coming, showing, stopped',

            // Thông báo lỗi của language
            'language.in'      => 'Ngôn ngữ không hợp lệ, chỉ chấp nhận: Tiếng Việt, Tiếng Anh, Tiếng Hàn, Tiếng Nhật, Tiếng Trung',

            'sort_by.in'       => 'Trường sắp xếp không hợp lệ',
            'sort_order.in'    => 'Hướng sắp xếp phải là asc hoặc desc',
        ];
    }
}
