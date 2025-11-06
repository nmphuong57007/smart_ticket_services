<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SeatUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Chỉ admin mới được cập nhật ghế
        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        $seatId = $this->route('id'); // Lấy ID ghế từ route

        return [
            'room_id'    => ['sometimes', 'exists:rooms,id'],
            'cinema_id'  => ['sometimes', 'exists:cinemas,id'],
            'seat_code'  => ['sometimes', 'string', 'max:10', "unique:seats,seat_code,{$seatId}"],
            'type'       => ['sometimes', 'in:normal,vip'],
            'status'     => ['sometimes', 'in:available,booked'],
            'price'      => ['sometimes', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.exists'       => 'Phòng chiếu không tồn tại.',
            'cinema_id.exists'     => 'Rạp không tồn tại.',
            'seat_code.unique'     => 'Mã ghế đã tồn tại.',
            'seat_code.max'        => 'Mã ghế tối đa 10 ký tự.',
            'type.in'              => 'Loại ghế không hợp lệ.',
            'status.in'            => 'Trạng thái ghế không hợp lệ.',
            'price.numeric'        => 'Giá ghế phải là số.',
            'price.min'            => 'Giá ghế phải >= 0.',
        ];
    }
}
