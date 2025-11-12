<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SeatUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Chỉ admin mới được phép chỉnh sửa ghế
        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        $seatId = $this->route('id'); // Lấy ID ghế từ route

        return [
            'cinema_id' => ['sometimes', 'exists:cinemas,id'],
            'room_id'   => ['sometimes', 'exists:rooms,id'],
            'seat_code' => [
                'sometimes',
                'string',
                'max:10',
                Rule::unique('seats', 'seat_code')->ignore($seatId),
            ],
            'type'   => ['sometimes', 'in:standard,vip,double'],
            'status' => ['sometimes', 'in:available,maintenance,disabled'], // ✅ cập nhật trạng thái vật lý
            'price'  => ['sometimes', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'cinema_id.exists' => 'Rạp chiếu không tồn tại.',
            'room_id.exists'   => 'Phòng chiếu không tồn tại.',

            'seat_code.unique' => 'Mã ghế đã tồn tại.',
            'seat_code.max'    => 'Mã ghế không được vượt quá 10 ký tự.',

            'type.in'          => 'Loại ghế không hợp lệ. (standard, vip, double)',
            'status.in'        => 'Trạng thái ghế không hợp lệ. (available, maintenance, disabled)',

            'price.numeric'    => 'Giá ghế phải là số.',
            'price.min'        => 'Giá ghế phải lớn hơn hoặc bằng 0.',
        ];
    }
}
