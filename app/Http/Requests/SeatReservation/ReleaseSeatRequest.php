<?php

namespace App\Http\Requests\SeatReservation;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\SeatReservation;
use Illuminate\Support\Facades\Auth;


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
            'seat_ids' => ['required', 'array', 'min:1'],
            'seat_ids.*' => ['distinct', 'exists:seats,id'],
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

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $showtimeId = $this->input('showtime_id');
            $seatIds = $this->input('seat_ids', []);
        $userId = Auth::id();

            if (empty($showtimeId) || empty($seatIds)) {
                return;
            }

            // Kiểm tra xem ghế có đang ở trạng thái reserved bởi user này không
            $invalidSeats = SeatReservation::where('showtime_id', $showtimeId)
                ->whereIn('seat_id', $seatIds)
                ->where(function ($q) use ($userId) {
                    $q->where('status', '!=', 'reserved')
                        ->orWhere('user_id', '!=', $userId);
                })
                ->pluck('seat_id')
                ->toArray();

            if (!empty($invalidSeats)) {
                $validator->errors()->add(
                    'seat_ids',
                    'Một hoặc nhiều ghế không thể hủy vì chúng không ở trạng thái giữ hoặc không thuộc về bạn.'
                );
            }
        });
    }
}
