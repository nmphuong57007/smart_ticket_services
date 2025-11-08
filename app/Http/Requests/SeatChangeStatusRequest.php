<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SeatChangeStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Chỉ admin mới được đổi trạng thái ghế
        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:available,booked,maintenance'],
            // Thêm các trạng thái ghế mà hệ thống bạn định nghĩa
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Trạng thái ghế là bắt buộc.',
            'status.string'   => 'Trạng thái ghế phải là chuỗi.',
            'status.in'       => 'Trạng thái ghế không hợp lệ.',
        ];
    }
}
