<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoomStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Chỉ admin hoặc staff mới được thêm phòng
        return $this->user() && in_array($this->user()->role, ['admin']);
    }

    public function rules(): array
    {
        return [
            'cinema_id'   => 'required|exists:cinemas,id',
            'name'        => 'required|string|max:50',
            // Cho phép gửi seat_map dạng array, Laravel sẽ tự cast sang JSON
            'seat_map'    => 'nullable|array',
            'seat_map.*'  => 'array', // Mỗi hàng ghế là 1 mảng
            'total_seats' => 'sometimes|integer|min:0',
            'status'      => 'sometimes|in:active,maintenance,closed',
        ];
    }

    public function messages(): array
    {
        return [
            'cinema_id.required' => 'Vui lòng chọn rạp cho phòng chiếu.',
            'cinema_id.exists'   => 'Rạp được chọn không tồn tại.',
            'name.required'      => 'Tên phòng chiếu không được để trống.',
            'seat_map.array'     => 'Sơ đồ ghế phải là định dạng mảng hợp lệ.',
            'status.in'          => 'Trạng thái phòng không hợp lệ.',
        ];
    }
}
