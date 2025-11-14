<?php

namespace App\Http\Services\Seat;

use App\Models\Seat;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SeatService
{
    /**
     * Lấy danh sách ghế với các bộ lọc (phòng, loại, trạng thái)
     */
    public function getSeats(array $filters = []): LengthAwarePaginator
    {
        $query = Seat::with(['room']);

        // Lọc theo phòng
        if (!empty($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        // Lọc theo loại ghế
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Lọc theo trạng thái (available, maintenance, broken…)
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Tìm theo seat_code
        if (!empty($filters['search'])) {
            $query->where('seat_code', 'like', "%{$filters['search']}%");
        }

        return $query->paginate($filters['per_page'] ?? 10);
    }

    /**
     * Lấy thông tin 1 ghế
     */
    public function getSeatById(int $id): ?Seat
    {
        return Seat::with(['room'])->find($id);
    }

    /**
     * Lấy danh sách ghế theo phòng
     */
    public function getSeatsByRoom(int $roomId)
    {
        return Seat::where('room_id', $roomId)
            ->with(['room'])
            ->orderBy('seat_code')
            ->get();
    }

    /**
     * CRUD ghế
     */
    public function createSeat(array $data): Seat
    {
        return Seat::create($data);
    }

    public function updateSeat(Seat $seat, array $data): Seat
    {
        $seat->update($data);
        return $seat;
    }

    public function deleteSeat(Seat $seat): bool
    {
        return $seat->delete();
    }

    /**
     * Thay đổi trạng thái ghế vật lý (available, maintenance, broken, disabled)
     */
    public function changeStatus(Seat $seat, string $status): Seat
    {
        $seat->status = $status;
        $seat->save();
        return $seat;
    }
}
