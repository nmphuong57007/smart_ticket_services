<?php

namespace App\Http\Services\SeatReservation;

use App\Models\Seat;
use App\Models\SeatReservation;
use App\Models\Showtime;
use Illuminate\Support\Facades\DB;
use Exception;
use Carbon\Carbon;

class SeatReservationService
{
    private int $reservationTimeout = 10; // phút
    public const TIMEOUT_SECONDS = 600; // 10 phút * 60 giây

    public function getSeatsByShowtime(int $showtimeId)
    {
        $showtime = Showtime::with('seats')->findOrFail($showtimeId);

        // Giải phóng ghế hết hạn
        $this->releaseExpiredSeats($showtimeId);

        $seats = $showtime->seats;

        // Lấy thông tin ghế đã được giữ hoặc đặt
        $reservations = SeatReservation::where('showtime_id', $showtimeId)
            ->whereIn('status', ['reserved', 'booked'])
            ->get()
            ->keyBy('seat_id');

        return $seats->map(function ($seat) use ($reservations) {
            $reservation = $reservations[$seat->id] ?? null;

            $status = $reservation?->status ?? 'available';

            return [
                'id' => $seat->id,
                'seat_code' => $seat->seat_code,
                'type' => $seat->type,
                'price' => $seat->price,
                'status' => $status,
                'reserved_by_user_id' => $status === 'reserved' ? $reservation->user_id : null,
                'reserved_at' => $status === 'reserved' && $reservation->reserved_at
                    ? $reservation->reserved_at->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d H:i:s')
                    : null,
                'booked_at' => $status === 'booked' && $reservation->booked_at
                    ? $reservation->booked_at->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d H:i:s')
                    : null,
                'booked_by_user_id' => $status === 'booked' ? $reservation->user_id : null,
            ];
        });
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
                    throw new Exception("Ghế ID {$seatId} không thuộc phòng chiếu này.");
                }
            }

            $this->releaseExpiredSeats($showtimeId);

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
                throw new Exception('Một số ghế đã được giữ hoặc đặt bởi người khác.');
            }

            $seatReservations = [];
            foreach ($seatIds as $seatId) {
                $seatReservations[] = SeatReservation::updateOrCreate(
                    ['showtime_id' => $showtimeId, 'seat_id' => $seatId],
                    [
                        'user_id' => $userId,
                        'status' => 'reserved',
                        'reserved_at' => now(),
                        'booked_at' => null,
                    ]
                );
            }

            return SeatReservation::whereIn('id', array_column($seatReservations, 'id'))
                ->with(['seat', 'showtime.movie', 'showtime.room.cinema'])
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
            $this->releaseExpiredSeats($showtimeId);

            foreach ($seatIds as $seatId) {
                $reservation = SeatReservation::firstOrNew([
                    'showtime_id' => $showtimeId,
                    'seat_id' => $seatId,
                ]);

                if (
                    $reservation->exists &&
                    $reservation->status === 'booked' &&
                    $reservation->user_id !== $userId
                ) {
                    throw new Exception("Ghế ID {$seatId} đã được đặt bởi người khác.");
                }

                $reservation->status = 'booked';
                $reservation->booked_at = now();
                $reservation->user_id = $userId ?? $reservation->user_id;
                $reservation->save();

                $seatReservations[] = $reservation->load(['seat', 'showtime.movie', 'showtime.room.cinema']);
            }

            return collect($seatReservations);
        });
    }


    /**
     * Hủy giữ ghế
     */
    public function releaseSeats(int $showtimeId, array $seatIds, int $userId)
    {
        return DB::transaction(function () use ($showtimeId, $seatIds, $userId) {

            // Giải phóng ghế quá hạn trước
            $this->releaseExpiredSeats($showtimeId);

            // Lấy reservation còn hiệu lực và thuộc user hiện tại
            $reservations = SeatReservation::where('showtime_id', $showtimeId)
                ->whereIn('seat_id', $seatIds)
                ->where('status', SeatReservation::STATUS_RESERVED)
                ->where('user_id', $userId)
                ->get();

            $foundSeatIds = $reservations->pluck('seat_id')->toArray();
            $notFoundSeatIds = array_diff($seatIds, $foundSeatIds);

            // Cập nhật trạng thái sang available
            if ($reservations->isNotEmpty()) {
                $reservations->each(function ($reservation) {
                    $reservation->update([
                        'status' => SeatReservation::STATUS_AVAILABLE,
                        'reserved_at' => null,
                        'user_id' => null,
                    ]);
                });
            }

            return [
                'success' => count($notFoundSeatIds) === 0,
                'message' => count($notFoundSeatIds) > 0
                    ? 'Một hoặc nhiều ghế không thể hủy vì chúng không ở trạng thái giữ hoặc không thuộc về bạn.'
                    : 'Hủy ghế thành công.',
                'released_seats' => $reservations->load(['seat', 'showtime.movie', 'showtime.room.cinema']),
                'failed_seat_ids' => $notFoundSeatIds,
            ];
        });
    }


    /**
     * Lấy danh sách ghế của user hiện tại
     */
    public function getMyReservations(int $userId)
    {
        $this->releaseExpiredSeatsForUser($userId);

        return SeatReservation::with(['seat', 'showtime.movie', 'showtime.room.cinema'])
            ->where('user_id', $userId)
            ->whereIn('status', ['reserved', 'booked'])
            ->orderByDesc('reserved_at')
            ->get();
    }

    /**
     * Lấy thống kê ghế
     */
    public function getSeatStats(int $showtimeId): array
    {
        $this->releaseExpiredSeats($showtimeId);

        $total = Seat::whereHas('room.showtimes', fn($q) => $q->where('id', $showtimeId))->count();
        $booked = SeatReservation::where('showtime_id', $showtimeId)->where('status', 'booked')->count();
        $reserved = SeatReservation::where('showtime_id', $showtimeId)
            ->where('status', 'reserved')
            ->where('reserved_at', '>', now()->subMinutes($this->reservationTimeout))
            ->count();

        return [
            'total' => $total,
            'booked' => $booked,
            'reserved' => $reserved,
            'available' => max($total - ($booked + $reserved), 0),
        ];
    }

    /**
     * Giải phóng ghế hết hạn
     */
    private function releaseExpiredSeats(int $showtimeId)
    {
        SeatReservation::where('showtime_id', $showtimeId)
            ->where('status', 'reserved')
            ->where('reserved_at', '<', now()->subMinutes($this->reservationTimeout))
            ->update([
                'status' => 'available',
                'reserved_at' => null,
                'user_id' => null,
            ]);
    }

    /**
     * Giải phóng ghế hết hạn theo user
     */
    private function releaseExpiredSeatsForUser(int $userId)
    {
        SeatReservation::where('user_id', $userId)
            ->where('status', 'reserved')
            ->where('reserved_at', '<', now()->subMinutes($this->reservationTimeout))
            ->update([
                'status' => 'available',
                'reserved_at' => null,
                'user_id' => null,
            ]);
    }
}
