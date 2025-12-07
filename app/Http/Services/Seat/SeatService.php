<?php

namespace App\Http\Services\Seat;

use App\Models\Seat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use App\Models\Seat as SeatModel;

class SeatService
{
    /**
     * Danh sách ghế theo suất chiếu + filter
     */
    public function getSeats(array $filters = [])
    {
        $query = Seat::query();

        if (!empty($filters['showtime_id'])) {
            $query->where('showtime_id', $filters['showtime_id']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where('seat_code', 'like', '%' . $filters['search'] . '%');
        }

        return $query
            ->orderBy('seat_code')
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Lấy danh sách ghế theo suất chiếu
     */
    public function getSeatsByShowtime(int $showtimeId): Collection
    {
        return Seat::where('showtime_id', $showtimeId)
            ->orderBy('seat_code')
            ->get();
    }

    /**
     * Lấy 1 ghế
     */
    public function getSeatById(int $id): ?Seat
    {
        return Seat::find($id);
    }

    /**
     * GIỮ GHẾ khi user nhấn nút THANH TOÁN (pending_payment)
     */
    public function holdSeats(array $seatIds): void
    {
        Seat::whereIn('id', $seatIds)
            ->where('status', SeatModel::STATUS_AVAILABLE)
            ->update(['status' => SeatModel::STATUS_PENDING_PAYMENT]);
    }

    /**
     * TRẢ GHẾ khi user hủy / thanh toán fail / timeout
     */
    public function releaseSeats(array $seatIds): void
    {
        Seat::whereIn('id', $seatIds)
            ->where('status', SeatModel::STATUS_PENDING_PAYMENT)
            ->update(['status' => SeatModel::STATUS_AVAILABLE]);
    }

    /**
     * BOOK GHẾ (khi thanh toán thành công)
     */
    public function bookSeats(array $seatIds): void
    {
        Seat::whereIn('id', $seatIds)->update([
            'status' => SeatModel::STATUS_BOOKED
        ]);
    }

    /**
     * Xóa 1 ghế
     */
    public function deleteSeat(Seat $seat): bool
    {
        return $seat->delete();
    }
}
