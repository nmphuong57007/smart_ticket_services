<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SeatChangeStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:available,maintenance,broken,disabled'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Trạng thái ghế là bắt buộc.',
            'status.string'   => 'Trạng thái ghế phải là chuỗi.',
            'status.in'       => 'Trạng thái ghế không hợp lệ. (available, maintenance, broken, disabled)',
        ];
    }
}
