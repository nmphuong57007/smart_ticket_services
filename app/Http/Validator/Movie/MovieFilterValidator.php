<?php

namespace App\Http\Validator\Movie;

use App\Http\Validator\BaseValidator;

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
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:coming,showing,stopped',
            'genre' => 'nullable|string|max:100',
            'sort_by' => 'nullable|in:id,title,release_date,duration,created_at,status,genre,format',
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
            'genre.string' => 'Thể loại phải là chuỗi ký tự',
            'genre.max' => 'Thể loại không được vượt quá 100 ký tự',
            'status.in' => 'Trạng thái phim phải là một trong: coming, showing, stopped',
            'sort_by.in' => 'Trường sắp xếp phải là một trong: id, title, release_date, duration, created_at, status, genre, format',
            'sort_order.in' => 'Hướng sắp xếp phải là asc hoặc desc'
        ];
    }
}