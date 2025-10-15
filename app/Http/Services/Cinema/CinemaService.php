<?php

namespace App\Http\Services\Cinema;

use App\Models\Cinema;
use App\Models\Showtime;

class CinemaService
{
    /**
     * Lấy danh sách rạp có phân trang
     */
    public function getCinemas(array $filters)
    {
        return Cinema::withCount('rooms')
            ->when($filters['name'] ?? null, fn($q, $name) => $q->where('name', 'like', "%$name%"))
            ->orderBy('id', 'asc')
            ->paginate($filters['per_page'] ?? 10);
    }

    /**
     * Lấy chi tiết rạp (kèm rooms)
     */
    public function getCinemaById(int $id)
    {
        return Cinema::with('rooms:id,cinema_id,name')->find($id);
    }

    /**
     * Lấy danh sách phòng theo rạp
     */
    public function getRoomsByCinema(int $cinemaId)
    {
        $cinema = Cinema::with('rooms:id,cinema_id,name')->find($cinemaId);

        return $cinema ? $cinema->rooms : [];
    }

    /**
     * Lấy toàn bộ lịch chiếu của 1 rạp (tổng hợp từ các phòng)
     */
    public function getShowtimesByCinema(int $cinemaId)
    {
        return Showtime::with(['movie:id,title', 'room:id,name,cinema_id'])
            ->whereHas('room', fn($q) => $q->where('cinema_id', $cinemaId))
            ->orderBy('show_date')
            ->orderBy('show_time')
            ->get();
    }

    /**
     * Thống kê tổng quan
     */
    public function getCinemaStatistics(): array
    {
        $totalCinemas = Cinema::count();
        $totalRooms = \App\Models\Room::count();

        return [
            'total_cinemas' => $totalCinemas,
            'total_rooms' => $totalRooms,
        ];
    }
}
