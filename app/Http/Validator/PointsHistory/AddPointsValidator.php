<?php

namespace App\Http\Validator\PointsHistory;

use App\Http\Validator\BaseValidator;

class AddPointsValidator extends BaseValidator
{
    /**
     * Get validation rules
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'points' => 'required|integer|not_in:0',
            'type' => 'required|in:earned,spent,refunded,bonus,penalty',
            'description' => 'required|string|max:255',
            'metadata' => 'nullable|array'
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
            'user_id.required' => 'ID người dùng không được để trống',
            'user_id.exists' => 'Không tìm thấy người dùng',
            'points.required' => 'Số điểm không được để trống',
            'points.integer' => 'Số điểm phải là số nguyên',
            'points.not_in' => 'Số điểm không được bằng 0',
            'type.required' => 'Loại giao dịch không được để trống',
            'type.in' => 'Loại giao dịch phải là một trong: earned, spent, refunded, bonus, penalty',
            'description.required' => 'Mô tả không được để trống',
            'description.string' => 'Mô tả phải là chuỗi ký tự',
            'description.max' => 'Mô tả không được vượt quá 255 ký tự',
            'metadata.array' => 'Metadata phải là mảng'
        ];
    }
}