<?php

namespace App\Http\Requests\SeatReservation;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\SeatReservation;
use Carbon\Carbon;

class ReserveSeatRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Có thể kiểm tra thêm quyền nếu cần (ví dụ: Auth::check())
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
            'seat_ids.required'    => 'Vui lòng chọn ít nhất một ghế.',
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
            $timeout = 10; // phút giữ ghế

            if (empty($showtimeId) || empty($seatIds)) {
                return;
            }

            // Kiểm tra xem có ghế nào đã bị giữ hoặc đặt chưa
            $conflictExists = SeatReservation::where('showtime_id', $showtimeId)
                ->whereIn('seat_id', $seatIds)
                ->where(function ($query) use ($timeout) {
                    $query->where('status', 'booked')
                        ->orWhere(function ($q) use ($timeout) {
                            $q->where('status', 'reserved')
                                ->where('reserved_at', '>', Carbon::now()->subMinutes($timeout));
                        });
                })
                ->exists();

            if ($conflictExists) {
                $validator->errors()->add('seat_ids', 'Một số ghế đã được giữ hoặc đặt. Vui lòng chọn ghế khác.');
            }
        });
    }
}
