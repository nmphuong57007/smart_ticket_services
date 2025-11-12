<?php

namespace App\Http\Requests\SeatReservation;

use Illuminate\Foundation\Http\FormRequest;

class ReleaseSeatRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Chỉ cho phép user đã đăng nhập
        return true;
    }

    public function rules(): array
    {
        return [
            'showtime_id' => ['required', 'exists:showtimes,id'],
            'seat_ids'    => ['required', 'array', 'min:1'],
            'seat_ids.*'  => ['distinct', 'exists:seats,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'showtime_id.required' => 'Vui lòng chọn suất chiếu.',
            'showtime_id.exists'   => 'Suất chiếu không tồn tại.',
            'seat_ids.required'    => 'Vui lòng chọn ít nhất một ghế để hủy.',
            'seat_ids.array'       => 'Dữ liệu ghế không hợp lệ.',
            'seat_ids.min'         => 'Cần chọn ít nhất 1 ghế.',
            'seat_ids.*.distinct'  => 'Có ghế bị trùng trong danh sách.',
            'seat_ids.*.exists'    => 'Một hoặc nhiều ghế không hợp lệ.',
        ];
    }
}
