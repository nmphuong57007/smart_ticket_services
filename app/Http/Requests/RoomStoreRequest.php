<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoomStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Chỉ admin được phép tạo phòng
        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            // Tên phòng — bắt buộc & không trùng
            'name'       => 'required|string|max:50|unique:rooms,name',

            // seat_map: mảng các hàng ghế
            'seat_map'         => 'required|array|min:1',
            'seat_map.*'       => 'array|min:1',

            // Cho phép ghế dạng string ("A1") hoặc object
            // Nếu là object thì code/type/price là tùy chọn
            'seat_map.*.*.code'  => 'sometimes|string|max:10',
            'seat_map.*.*.type'  => 'sometimes|string|max:20',
            'seat_map.*.*.price' => 'sometimes|numeric|min:0|max:1000000',

            // Trạng thái phòng khi tạo
            'status'     => 'sometimes|in:active,maintenance,closed',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên phòng chiếu là bắt buộc.',
            'name.unique'   => 'Tên phòng chiếu đã tồn tại.',
            'name.max'      => 'Tên phòng không vượt quá 50 ký tự.',

            'seat_map.required' => 'Sơ đồ ghế là bắt buộc.',
            'seat_map.array'    => 'Sơ đồ ghế phải là dạng mảng.',
            'seat_map.*.array'  => 'Mỗi hàng ghế phải là dạng mảng.',

            'seat_map.*.*.code.string'  => 'Mã ghế phải là chuỗi ký tự.',
            'seat_map.*.*.price.numeric' => 'Giá ghế phải là số hợp lệ.',
            'seat_map.*.*.price.min'     => 'Giá ghế không được âm.',

            'status.in' => 'Trạng thái phòng không hợp lệ.',
        ];
    }
}
