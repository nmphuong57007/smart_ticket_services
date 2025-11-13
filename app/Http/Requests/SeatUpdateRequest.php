<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SeatUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        $seatId = $this->route('id');

        return [
            'room_id' => ['sometimes', 'exists:rooms,id'],

            // Unique theo room_id + seat_code
            'seat_code' => [
                'sometimes',
                'string',
                'max:10',
                Rule::unique('seats', 'seat_code')
                    ->ignore($seatId)
                    ->where(fn($q) => $q->where('room_id', $this->room_id)),
            ],

            // Loại ghế đúng: normal / vip
            'type' => ['sometimes', 'in:normal,vip'],

            // Trạng thái vật lý đúng: available, maintenance, broken, disabled
            'status' => ['sometimes', 'in:available,maintenance,broken,disabled'],

            'price' => ['sometimes', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.exists'     => 'Phòng chiếu không tồn tại.',

            'seat_code.unique'   => 'Mã ghế đã tồn tại trong phòng này.',
            'seat_code.max'      => 'Mã ghế không được vượt quá 10 ký tự.',

            'type.in'            => 'Loại ghế không hợp lệ. (normal, vip)',

            'status.in'          => 'Trạng thái ghế không hợp lệ. (available, maintenance, broken, disabled)',

            'price.numeric'      => 'Giá ghế phải là số.',
            'price.min'          => 'Giá ghế phải lớn hơn hoặc bằng 0.',
        ];
    }
}
