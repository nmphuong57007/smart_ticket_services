<?php

namespace App\Http\Validator\PointsHistory;

use App\Http\Validator\BaseValidator;

class PointsHistoryFilterValidator extends BaseValidator
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
            'type' => 'nullable|in:earned,spent,refunded,bonus,penalty',
            'source' => 'nullable|string|max:100',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date'
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
            'type.in' => 'Loại giao dịch phải là một trong: earned, spent, refunded, bonus, penalty',
            'source.string' => 'Nguồn phải là chuỗi ký tự',
            'source.max' => 'Nguồn không được vượt quá 100 ký tự',
            'from_date.date' => 'Từ ngày phải là định dạng ngày hợp lệ',
            'to_date.date' => 'Đến ngày phải là định dạng ngày hợp lệ',
            'to_date.after_or_equal' => 'Đến ngày phải sau hoặc bằng từ ngày'
        ];
    }
}