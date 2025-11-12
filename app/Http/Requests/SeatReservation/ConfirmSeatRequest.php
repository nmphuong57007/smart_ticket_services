<?php

namespace App\Http\Requests\SeatReservation;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\SeatReservation;
use Illuminate\Support\Carbon; 

class ConfirmSeatRequest extends FormRequest
{
    public function authorize(): bool
    {
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
            'seat_ids.required'    => 'Vui lòng chọn ít nhất một ghế để xác nhận.',
            'seat_ids.array'       => 'Dữ liệu ghế không hợp lệ.',
            'seat_ids.min'         => 'Cần chọn ít nhất một ghế.',
            'seat_ids.*.distinct'  => 'Có ghế bị trùng trong danh sách.',
            'seat_ids.*.exists'    => 'Ghế không hợp lệ.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $showtimeId = $this->input('showtime_id');
            $seatIds    = $this->input('seat_ids', []);
            $timeout    = 10; // phút hết hạn giữ ghế

            if (empty($showtimeId) || empty($seatIds)) {
                return;
            }

            // Lọc ra các ghế không còn hợp lệ để xác nhận
            $invalidSeats = SeatReservation::where('showtime_id', $showtimeId)
                ->whereIn('seat_id', $seatIds)
                ->where(function ($query) use ($timeout) {
                    $query->where('status', '!=', 'reserved')
                        ->orWhere('reserved_at', '<', Carbon::now()->subMinutes($timeout));
                })
                ->pluck('seat_id')
                ->toArray();

            if (!empty($invalidSeats)) {
                $validator->errors()->add(
                    'seat_ids',
                    'Một số ghế không thể xác nhận (đã hết hạn giữ hoặc bị người khác đặt).'
                );
            }
        });
    }
}
