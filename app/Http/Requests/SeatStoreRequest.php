<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SeatStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Chỉ admin mới được tạo ghế
        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'room_id'   => 'required|exists:rooms,id',
            'cinema_id' => 'required|exists:cinemas,id',
            'seat_code' => 'required|string|max:10|unique:seats,seat_code',
            'type'      => 'required|in:normal,vip',
            'status'    => 'sometimes|in:available,booked',
            'price'     => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.required'   => 'Phòng chiếu là bắt buộc.',
            'room_id.exists'     => 'Phòng chiếu không tồn tại.',
            'cinema_id.required' => 'Rạp là bắt buộc.',
            'cinema_id.exists'   => 'Rạp không tồn tại.',
            'seat_code.required' => 'Mã ghế là bắt buộc.',
            'seat_code.unique'   => 'Mã ghế đã tồn tại.',
            'type.required'      => 'Loại ghế là bắt buộc.',
            'type.in'            => 'Loại ghế không hợp lệ.',
            'status.in'          => 'Trạng thái ghế không hợp lệ.',
            'price.required'     => 'Giá ghế là bắt buộc.',
            'price.numeric'      => 'Giá ghế phải là số.',
            'price.min'          => 'Giá ghế phải >= 0.',
        ];
    }
}
