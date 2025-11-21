<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SeatChangeStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Staff + Admin được đổi thủ công
        return $this->user() && in_array($this->user()->role, ['admin', 'staff']);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:available,selected,booked'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Trạng thái ghế là bắt buộc.',
            'status.in'       => 'Trạng thái ghế không hợp lệ. (available, selected, booked)',
        ];
    }
}
