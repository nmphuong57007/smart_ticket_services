<?php

namespace App\Http\Services\Seat;

use App\Models\Seat;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SeatService
{
    /**
     * Danh sách ghế theo suất chiếu + filter
     */
    public function getSeats(array $filters = []): LengthAwarePaginator
    {
        $query = Seat::query();

        // Bắt buộc phải có showtime_id
        if (!empty($filters['showtime_id'])) {
            $query->where('showtime_id', $filters['showtime_id']);
        }

        // Lọc theo loại ghế
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Lọc theo trạng thái ghế
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Search seat_code
        if (!empty($filters['search'])) {
            $query->where('seat_code', 'like', '%'.$filters['search'].'%');
        }

        return $query
            ->orderBy('seat_code')
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Lấy ghế theo showtime (trả về collection, không phân trang)
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
     * Tạo ghế — dùng nội bộ khi phát sinh ghế theo suất chiếu
     */
    public function createSeat(array $data): Seat
    {
        return Seat::create($data);
    }

    /**
     * Cập nhật
     */
    public function updateSeat(Seat $seat, array $data): Seat
    {
        $seat->update($data);
        return $seat;
    }

    /**
     * Xóa ghế
     */
    public function deleteSeat(Seat $seat): bool
    {
        return $seat->delete();
    }

    /**
     * Thay đổi trạng thái ghế khi đặt vé
     * available / selected / booked
     */
    public function changeStatus(Seat $seat, string $status): Seat
    {
        $seat->status = $status;
        $seat->save();
        return $seat;
    }
}
