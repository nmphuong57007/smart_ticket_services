<?php

namespace App\Http\Services\Seat;

use App\Models\Seat;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SeatService
{
    public function getSeats(array $filters = []): LengthAwarePaginator
    {
        $query = Seat::with(['room', 'cinema'])->withActiveReservation();

        if (!empty($filters['cinema_id'])) $query->where('cinema_id', $filters['cinema_id']);
        if (!empty($filters['room_id'])) $query->where('room_id', $filters['room_id']);
        if (!empty($filters['type'])) $query->where('type', $filters['type']);
        if (!empty($filters['search'])) $query->where('seat_code', 'like', "%{$filters['search']}%");

        $seats = $query->paginate($filters['per_page'] ?? 10);

        if (!empty($filters['status'])) {
            $seats->getCollection()->transform(fn($seat) => $seat->current_status === $filters['status'] ? $seat : null);
            $seats->setCollection($seats->getCollection()->filter());
        }

        return $seats;
    }

    public function getSeatById(int $id): ?Seat
    {
        return Seat::with(['room', 'cinema'])->withActiveReservation()->find($id);
    }

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
}
