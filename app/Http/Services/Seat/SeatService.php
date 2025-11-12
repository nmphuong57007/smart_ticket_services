<?php

namespace App\Http\Services\Seat;

use App\Models\Seat;
use App\Models\Showtime;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SeatService
{
    /**
     * Lấy danh sách ghế với các bộ lọc (phòng, rạp, loại, trạng thái)
     */
    public function getSeats(array $filters = []): LengthAwarePaginator
    {
        $query = Seat::with(['room', 'cinema'])
            ->withActiveReservation();

        if (!empty($filters['cinema_id'])) {
            $query->where('cinema_id', $filters['cinema_id']);
        }

        if (!empty($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['search'])) {
            $query->where('seat_code', 'like', "%{$filters['search']}%");
        }

        $seats = $query->paginate($filters['per_page'] ?? 10);

        if (!empty($filters['status'])) {
            // Lọc theo current_status sau khi eager load
            $seats->setCollection(
                $seats->getCollection()->filter(fn($seat) => $seat->current_status === $filters['status'])
            );
        }

        return $seats;
    }

    /**
     * Lấy thông tin chi tiết 1 ghế
     */
    public function getSeatById(int $id): ?Seat
    {
        return Seat::with(['room', 'cinema'])
            ->withActiveReservation()
            ->find($id);
    }

    /**
     * Lấy danh sách ghế theo phòng
     */
    public function getSeatsByRoom(int $roomId)
    {
        return Seat::where('room_id', $roomId)
            ->with(['room', 'cinema', 'reservations'])
            ->orderBy('seat_code')
            ->get();
    }

    /**
     * Lấy danh sách ghế theo suất chiếu (có trạng thái thực tế)
     */
    public function getSeatsByShowtime(int $showtimeId)
    {
        $showtime = Showtime::with('room.seats.reservations')->findOrFail($showtimeId);

        $seats = $showtime->room->seats;

        foreach ($seats as $seat) {
            $seat->current_status = $seat->getStatusForShowtime($showtimeId);
        }

        return $seats;
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

    public function changeStatus(Seat $seat, string $status): Seat
    {
        $seat->status = $status;
        $seat->save();
        return $seat;
    }
}
