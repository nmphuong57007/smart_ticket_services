<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SeatStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Chỉ admin được thêm ghế
        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'room_id'   => ['required', 'exists:rooms,id'],

            // Unique theo room, không phải toàn bảng
            'seat_code' => [
                'required',
                'string',
                'max:10',
                'unique:seats,seat_code,NULL,id,room_id,' . $this->room_id
            ],

            // Loại ghế: chỉ normal hoặc vip
            'type'      => ['required', 'in:normal,vip'],

            // Trạng thái vật lý
            'status'    => ['nullable', 'in:available,maintenance,broken,disabled'],

            'price'     => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.required' => 'Phòng chiếu là bắt buộc.',
            'room_id.exists'   => 'Phòng chiếu không tồn tại.',

            'seat_code.required' => 'Mã ghế là bắt buộc.',
            'seat_code.string'   => 'Mã ghế phải là chuỗi.',
            'seat_code.max'      => 'Mã ghế tối đa 10 ký tự.',
            'seat_code.unique'   => 'Mã ghế đã tồn tại trong phòng này.',

            'type.required'      => 'Loại ghế là bắt buộc.',
            'type.in'            => 'Loại ghế chỉ có thể là normal hoặc vip.',

            'status.in'          => 'Trạng thái không hợp lệ (available, maintenance, broken, disabled).',

            'price.required'     => 'Giá ghế là bắt buộc.',
            'price.numeric'      => 'Giá ghế phải là số.',
            'price.min'          => 'Giá ghế phải >= 0.',
        ];
    }
}
