<?php

namespace App\Http\Services\SeatReservation;

use App\Models\Seat;
use App\Models\SeatReservation;
use App\Models\Showtime;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class SeatReservationService
{
    private int $reservationTimeout = 10; // phút

    /**
     * Lấy danh sách ghế kèm trạng thái
     */
    public function getSeatsByShowtime(int $showtimeId)
    {
        $showtime = Showtime::with('seats')->findOrFail($showtimeId);
        $seats = $showtime->seats;

        $reserved = SeatReservation::active($this->reservationTimeout)
            ->where('showtime_id', $showtimeId)
            ->pluck('status', 'seat_id')
            ->toArray();

        return $seats->map(fn($seat) => [
            'id' => $seat->id,
            'seat_code' => $seat->seat_code,
            'type' => $seat->type,
            'price' => $seat->price,
            'status' => $reserved[$seat->id] ?? 'available',
        ]);
    }

    /**
     * Giữ ghế tạm thời và trả về data ghế
     */
    public function reserveSeats(int $showtimeId, array $seatIds, ?int $userId = null)
    {
        $timeout = $this->reservationTimeout;

        return DB::transaction(function () use ($showtimeId, $seatIds, $userId, $timeout) {
            $showtime = Showtime::with('room')->findOrFail($showtimeId);
            $validSeatIds = Seat::where('room_id', $showtime->room_id)->pluck('id')->toArray();

            foreach ($seatIds as $seatId) {
                if (!in_array($seatId, $validSeatIds)) {
                    throw new Exception("Ghế ID {$seatId} không thuộc phòng của suất chiếu này.");
                }
            }

            $conflicts = SeatReservation::active($timeout)
                ->where('showtime_id', $showtimeId)
                ->whereIn('seat_id', $seatIds)
                ->lockForUpdate()
                ->exists();

            if ($conflicts) {
                throw new Exception('Một số ghế đã được giữ hoặc đặt. Vui lòng chọn ghế khác.');
            }

            foreach ($seatIds as $seatId) {
                SeatReservation::updateOrCreate(
                    ['showtime_id' => $showtimeId, 'seat_id' => $seatId],
                    ['user_id' => $userId, 'status' => 'reserved', 'reserved_at' => Carbon::now(), 'booked_at' => null]
                );
            }

            // Trả về data ghế vừa giữ
            return Seat::whereIn('id', $seatIds)
                ->get()
                ->map(fn($seat) => [
                    'id' => $seat->id,
                    'seat_code' => $seat->seat_code,
                    'type' => $seat->type,
                    'price' => $seat->price,
                    'status' => 'reserved',
                ]);
        });
    }

    /**
     * Xác nhận đặt ghế và trả về data ghế
     */
    public function confirmBooking(int $showtimeId, array $seatIds)
    {
        $timeout = $this->reservationTimeout;

        $expiredSeats = SeatReservation::where('showtime_id', $showtimeId)
            ->whereIn('seat_id', $seatIds)
            ->where('status', 'reserved')
            ->where('reserved_at', '<', Carbon::now()->subMinutes($timeout))
            ->pluck('seat_id')
            ->toArray();

        if (!empty($expiredSeats)) {
            throw new Exception('Một số ghế đã hết hạn giữ, vui lòng chọn lại.');
        }

        SeatReservation::where('showtime_id', $showtimeId)
            ->whereIn('seat_id', $seatIds)
            ->update(['status' => 'booked', 'booked_at' => Carbon::now()]);

        // Trả về data ghế vừa đặt
        return Seat::whereIn('id', $seatIds)
            ->get()
            ->map(fn($seat) => [
                'id' => $seat->id,
                'seat_code' => $seat->seat_code,
                'type' => $seat->type,
                'price' => $seat->price,
                'status' => 'booked',
            ]);
    }

    /**
     * Dọn ghế hết hạn giữ
     */
    public function releaseExpiredReservations(): int
    {
        return SeatReservation::where('status', 'reserved')
            ->where('reserved_at', '<', Carbon::now()->subMinutes($this->reservationTimeout))
            ->update(['status' => 'available', 'reserved_at' => null, 'user_id' => null]);
    }

    /**
     * Hủy giữ ghế và trả về data ghế
     */
    public function releaseSeats(int $showtimeId, array $seatIds, ?int $userId = null)
    {
        return DB::transaction(function () use ($showtimeId, $seatIds, $userId) {
            $query = SeatReservation::where('showtime_id', $showtimeId)
                ->whereIn('seat_id', $seatIds)
                ->where('status', 'reserved');

            if ($userId) $query->where('user_id', $userId);

            $updated = $query->update([
                'status' => 'available',
                'reserved_at' => null,
                'user_id' => null,
            ]);

            if ($updated === 0) {
                throw new Exception('Không có ghế nào có thể hủy hoặc ghế đã được đặt.');
            }

            // Trả về data ghế vừa hủy
            return Seat::whereIn('id', $seatIds)
                ->get()
                ->map(fn($seat) => [
                    'id' => $seat->id,
                    'seat_code' => $seat->seat_code,
                    'type' => $seat->type,
                    'price' => $seat->price,
                    'status' => 'available',
                ]);
        });
    }
}
