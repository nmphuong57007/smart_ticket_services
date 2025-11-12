<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SeatStoreRequest extends FormRequest
{
    public function authorize(): bool
    {

        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'cinema_id' => ['required', 'exists:cinemas,id'],
            'room_id'   => ['required', 'exists:rooms,id'],
            'seat_code' => ['required', 'string', 'max:10', 'unique:seats,seat_code'],
            'type'      => ['required', 'in:standard,vip,double'],
            'status'    => ['nullable', 'in:available,reserved,booked'],
            'price'     => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'cinema_id.required' => 'Rạp chiếu là bắt buộc.',
            'cinema_id.exists'   => 'Rạp chiếu không tồn tại.',
            'room_id.required'   => 'Phòng chiếu là bắt buộc.',
            'room_id.exists'     => 'Phòng chiếu không tồn tại.',

            'seat_code.required' => 'Mã ghế là bắt buộc.',
            'seat_code.string'   => 'Mã ghế phải là chuỗi ký tự.',
            'seat_code.max'      => 'Mã ghế không được vượt quá 10 ký tự.',
            'seat_code.unique'   => 'Mã ghế đã tồn tại.',

            'type.required'      => 'Loại ghế là bắt buộc.',
            'type.in'            => 'Loại ghế không hợp lệ. (standard, vip, double)',

            'status.in'          => 'Trạng thái ghế không hợp lệ.',
            'price.required'     => 'Giá ghế là bắt buộc.',
            'price.numeric'      => 'Giá ghế phải là số.',
            'price.min'          => 'Giá ghế phải lớn hơn hoặc bằng 0.',
        ];
    }
}
