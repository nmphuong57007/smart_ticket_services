<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoomUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Chỉ admin hoặc staff mới có quyền cập nhật phòng
        return $this->user() && in_array($this->user()->role, ['admin']);
    }

    public function rules(): array
    {
        return [
            'cinema_id'   => 'sometimes|exists:cinemas,id',
            'name'        => 'sometimes|string|max:50',
            'seat_map'    => 'sometimes|nullable|array',
            'seat_map.*'  => 'array',
            'total_seats' => 'sometimes|integer|min:0',
            'status'      => 'sometimes|in:active,maintenance,closed',
        ];
    }

    public function messages(): array
    {
        return [
            'cinema_id.exists'   => 'Rạp chiếu được chọn không tồn tại.',
            'name.string'        => 'Tên phòng chiếu phải là chuỗi ký tự.',
            'seat_map.array'     => 'Sơ đồ ghế phải ở dạng mảng hợp lệ.',
            'total_seats.integer' => 'Tổng số ghế phải là số nguyên.',
            'status.in'          => 'Trạng thái phòng không hợp lệ.',
        ];
    }
}
