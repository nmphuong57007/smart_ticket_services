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
     * Lấy danh sách ghế theo suất chiếu kèm trạng thái
     */
    public function getSeatsByShowtime(int $showtimeId)
    {
        $showtime = Showtime::with('seats')->findOrFail($showtimeId);
        $seats = $showtime->seats;

        // Lấy trạng thái đang active (reserved chưa hết hạn) hoặc booked
        $reserved = SeatReservation::where('showtime_id', $showtimeId)
            ->where(function ($q) {
                $q->where('status', 'booked')
                    ->orWhere(function ($q2) {
                        $q2->where('status', 'reserved')
                            ->where('reserved_at', '>', now()->subMinutes($this->reservationTimeout));
                    });
            })
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
     * Giữ ghế tạm thời
     */
    public function reserveSeats(int $showtimeId, array $seatIds, ?int $userId = null)
    {
        $timeout = $this->reservationTimeout;

        return DB::transaction(function () use ($showtimeId, $seatIds, $userId, $timeout) {
            $showtime = Showtime::with('room')->findOrFail($showtimeId);
            $validSeatIds = Seat::where('room_id', $showtime->room_id)->pluck('id')->toArray();

            foreach ($seatIds as $seatId) {
                if (!in_array($seatId, $validSeatIds)) {
                    throw new Exception("Ghế ID {$seatId} không thuộc phòng.");
                }
            }

            // Kiểm tra xung đột
            $conflicts = SeatReservation::where('showtime_id', $showtimeId)
                ->whereIn('seat_id', $seatIds)
                ->where(function ($q) use ($timeout) {
                    $q->where('status', 'booked')
                        ->orWhere(function ($q2) use ($timeout) {
                            $q2->where('status', 'reserved')
                                ->where('reserved_at', '>', now()->subMinutes($timeout));
                        });
                })
                ->lockForUpdate()
                ->exists();

            if ($conflicts) {
                throw new Exception('Một số ghế đã được giữ hoặc đặt.');
            }

            $seatReservations = [];
            foreach ($seatIds as $seatId) {
                $seatReservations[] = SeatReservation::updateOrCreate(
                    ['showtime_id' => $showtimeId, 'seat_id' => $seatId],
                    [
                        'user_id' => $userId,
                        'status' => 'reserved',
                        'reserved_at' => now(),
                        'booked_at' => null
                    ]
                );
            }

            return SeatReservation::whereIn('id', array_map(fn($s) => $s->id, $seatReservations))
                ->with('seat')
                ->get();
        });
    }

    /**
     * Xác nhận đặt ghế
     */
    public function confirmBooking(int $showtimeId, array $seatIds, ?int $userId = null)
    {
        $timeout = $this->reservationTimeout;

        return DB::transaction(function () use ($showtimeId, $seatIds, $userId, $timeout) {
            $seatReservations = [];

            foreach ($seatIds as $seatId) {
                $reservation = SeatReservation::firstOrNew([
                    'showtime_id' => $showtimeId,
                    'seat_id' => $seatId
                ]);

                // Nếu ghế đã được reserved nhưng hết hạn, throw lỗi
                if ($reservation->exists && $reservation->status === 'reserved' && $reservation->reserved_at < now()->subMinutes($timeout)) {
                    throw new Exception("Ghế ID {$seatId} đã hết hạn giữ.");
                }

                // Nếu ghế đã booked bởi người khác
                if ($reservation->exists && $reservation->status === 'booked' && $reservation->user_id !== $userId) {
                    throw new Exception("Ghế ID {$seatId} đã được đặt.");
                }

                $reservation->status = 'booked';
                $reservation->booked_at = now();
                $reservation->user_id = $userId ?? $reservation->user_id;
                $reservation->save();

                $seatReservations[] = $reservation->load('seat');
            }

            return collect($seatReservations);
        });
    }

    /**
     * Hủy giữ ghế
     */
    public function releaseSeats(int $showtimeId, array $seatIds, ?int $userId = null)
    {
        return DB::transaction(function () use ($showtimeId, $seatIds, $userId) {
            $query = SeatReservation::where('showtime_id', $showtimeId)
                ->whereIn('seat_id', $seatIds)
                ->where('status', 'reserved');

            if ($userId) {
                $query->where('user_id', $userId);
            }

            $updated = $query->update([
                'status' => 'available',
                'reserved_at' => null,
                'user_id' => null
            ]);

            if ($updated === 0) {
                throw new Exception('Không có ghế nào có thể hủy.');
            }

            return SeatReservation::where('showtime_id', $showtimeId)
                ->whereIn('seat_id', $seatIds)
                ->with('seat')
                ->get();
        });
    }
}
