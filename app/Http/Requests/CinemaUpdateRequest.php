<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CinemaUpdateRequest extends FormRequest
{
    /**
     * Chỉ cho phép admin cập nhật rạp chiếu
     */
    public function authorize(): bool
    {
        return $this->user() && in_array($this->user()->role, ['admin']);
    }

    /**
     * Quy tắc validate cho cập nhật rạp
     */
    public function rules(): array
    {
        return [
            'name'    => 'sometimes|nullable|string|max:255',
            'address' => 'sometimes|nullable|string|max:500',
            'phone'   => 'sometimes|nullable|string|max:20',
            'status'  => 'sometimes|in:active,inactive',
        ];
    }

    /**
     * Thông báo lỗi tùy chỉnh
     */
    public function messages(): array
    {
        return [
            'name.string'      => 'Tên rạp phải là chuỗi ký tự.',
            'name.max'         => 'Tên rạp không được vượt quá 255 ký tự.',

            'address.string'   => 'Địa chỉ rạp phải là chuỗi ký tự.',
            'address.max'      => 'Địa chỉ rạp không được vượt quá 500 ký tự.',

            'phone.string'     => 'Số điện thoại phải là chuỗi ký tự.',
            'phone.max'        => 'Số điện thoại không được vượt quá 20 ký tự.',

            'status.in'        => 'Trạng thái phải là "active" hoặc "inactive".',
        ];
    }
}
