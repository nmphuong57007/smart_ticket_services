<?php

namespace App\Http\Services\Showtime;

use App\Models\Showtime;

class ShowtimeService
{
    /**
     * Lấy lịch chiếu theo rạp/ngày, có thể filter theo phim
     *
     * @param array $filters ['room_id', 'movie_id', 'show_date', 'from_date', 'to_date', 'per_page']
     */
    public function getShowtimes(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Showtime::with([
            'movie:id,title,poster,release_date',
            'room:id,name,cinema_id'
        ])
            ->when($filters['room_id'] ?? null, fn($query, $roomId) => $query->where('room_id', $roomId))
            ->when($filters['movie_id'] ?? null, fn($query, $movieId) => $query->where('movie_id', $movieId))
            ->when($filters['show_date'] ?? null, fn($query, $date) => $query->where('show_date', $date))
            ->when($filters['from_date'] ?? null, fn($query, $fromDate) => $query->whereDate('show_date', '>=', $fromDate))
            ->when($filters['to_date'] ?? null, fn($query, $toDate) => $query->whereDate('show_date', '<=', $toDate))
            ->orderBy('show_time', 'asc')
            ->paginate($filters['per_page'] ?? 10);
    }

    /**
     * Lấy tất cả các ngày chiếu của một phòng
     *
     * @param int $roomId
     * @return array
     */
    public function getShowDatesByRoom(int $roomId): array
    {
        return Showtime::where('room_id', $roomId)
            ->select('show_date')
            ->distinct()
            ->orderBy('show_date', 'asc')
            ->pluck('show_date')
            ->toArray();
    }

    /**
     * Lấy tất cả phòng có lịch chiếu
     *
     * @return array
     */
    public function getRoomsWithShowtimes(): array
    {
        return Showtime::whereNotNull('room_id')
            ->with('room:id,name,cinema_id')
            ->select('room_id')
            ->distinct()
            ->get()
            ->pluck('room')
            ->filter() // loại bỏ room null
            ->values()
            ->toArray();
    }

    /**
     * Thống kê lịch chiếu theo phim / phòng / ngày
     *
     * @return array
     */
    public function getShowtimeStatistics(): array
    {
        $totalShowtimes = Showtime::count();
        $totalMovies = Showtime::distinct('movie_id')->count('movie_id');
        $totalRooms = Showtime::distinct('room_id')->count('room_id');

        return [
            'total_showtimes' => $totalShowtimes,
            'total_movies' => $totalMovies,
            'total_rooms' => $totalRooms,
        ];
    }
}
