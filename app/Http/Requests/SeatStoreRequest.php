<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SeatStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && in_array($this->user()->role, ['admin']);
    }

    public function rules(): array
    {
        return [
            'room_id'   => 'required|exists:rooms,id',
            'seat_code' => 'required|string|max:10|unique:seats,seat_code',
            'type'      => 'required|in:normal,vip',
            'status'    => 'sometimes|in:available,booked',
            'price'     => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.required' => 'Phòng chiếu là bắt buộc.',
            'seat_code.unique' => 'Mã ghế đã tồn tại.',
        ];
    }
}
