<?php

namespace App\Http\Services\Seat;

use App\Models\Seat;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SeatService
{
    /**
     * Lấy danh sách ghế với các bộ lọc (phòng, rạp, loại, trạng thái)
     */
    public function getSeats(array $filters = []): LengthAwarePaginator
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
            $query->where('seat_code', 'like', "%{$filters['search']}%");
        }

        return $query->paginate($filters['per_page'] ?? 10);
    }

    /**
     * Lấy thông tin chi tiết 1 ghế
     */
    public function getSeatById(int $id): ?Seat
    {
        return Seat::with(['room', 'cinema'])->find($id);
    }

    /**
     * Lấy danh sách ghế theo phòng
     */
    public function getSeatsByRoom(int $roomId)
    {
        return Seat::where('room_id', $roomId)
            ->with(['room', 'cinema'])
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
     * Thay đổi trạng thái ghế (available, maintenance, disabled)
     */
    public function changeStatus(Seat $seat, string $status): Seat
    {
        $seat->status = $status;
        $seat->save();
        return $seat;
    }
}
