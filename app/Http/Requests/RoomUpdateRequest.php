<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoomUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            // Cho phép sửa name nhưng không trùng phòng khác
            'name' => 'sometimes|string|max:50|unique:rooms,name,' . $this->route('id'),

            // Chỉ validate seat_map nếu UI gửi lên
            'seat_map'   => 'sometimes|array',
            'seat_map.*' => 'array',

            // Ghế dạng string hoặc object
            'seat_map.*.*.code'  => 'sometimes|string|max:10',
            'seat_map.*.*.type'  => 'sometimes|string|max:20',
            'seat_map.*.*.price' => 'sometimes|numeric|min:0|max:1000000',
            'seat_map.*.*.status' => 'sometimes|string|in:active,broken,blocked',


            // Trạng thái
            'status' => 'sometimes|in:active,maintenance,closed',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Tên phòng chiếu đã tồn tại.',
            'name.max'    => 'Tên phòng chiếu không vượt quá 50 ký tự.',

            'seat_map.array'      => 'Sơ đồ ghế phải là dạng mảng.',
            'seat_map.*.array'    => 'Mỗi hàng ghế phải là dạng mảng.',

            'status.in' => 'Trạng thái phòng không hợp lệ.',
        ];
    }
}
