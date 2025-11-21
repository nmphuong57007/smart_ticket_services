<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SeatStatusUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:active,broken,blocked',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Trạng thái ghế là bắt buộc.',
            'status.in' => 'Trạng thái phải là active, broken hoặc blocked.',
        ];
    }
}
