<?php

namespace App\Http\Services\Seat;

use App\Models\Seat;
use App\Models\SeatReservation;

class SeatService
{
    /**
     * Lấy danh sách ghế với filter & pagination
     *
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getSeats(array $filters = [])
    {
        $query = Seat::with(['room', 'cinema']);

        if (!empty($filters['cinema_id'])) {
            $query->where('cinema_id', $filters['cinema_id']);
        }

        if (!empty($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
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

        return $query->paginate($filters['per_page'] ?? 10);
    }

    /**
     * Lấy thông tin ghế theo ID
     */
    public function getSeatById(int $id): ?Seat
    {
        return Seat::with(['room', 'cinema'])->find($id);
    }

    /**
     * Tạo mới ghế
     */
    public function createSeat(array $data): Seat
    {
        return Seat::create($data);
    }

    /**
     * Cập nhật ghế
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

}
