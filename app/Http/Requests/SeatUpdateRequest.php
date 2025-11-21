<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SeatUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            // Chỉ cho phép đổi status ghế trong suất chiếu
            'status' => ['required', 'in:available,selected,booked'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Trạng thái là bắt buộc.',
            'status.in'       => 'Trạng thái ghế không hợp lệ.',
        ];
    }
}
