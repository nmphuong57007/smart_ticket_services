<?php

namespace App\Http\Services\Seat;

use App\Models\Seat;

class SeatService
{
    public function getSeats(array $filters = [])
    {
        $query = Seat::with('room');

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

    public function getSeatById(int $id): ?Seat
    {
        return Seat::with('room')->find($id);
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
    public function getSeatsWithReservationStatus(int $roomId, int $showtimeId)
    {
        return Seat::where('room_id', $roomId)
            ->with(['reservations' => function ($q) use ($showtimeId) {
                $q->where('showtime_id', $showtimeId);
            }])
            ->get()
            ->map(function ($seat) {
                $reservation = $seat->reservations->first();
                $seat->current_status = $reservation->status ?? 'available';
                return $seat;
            });
    }
}
