<?php

namespace App\Http\Services\Showtime;

use App\Models\Showtime;

class ShowtimeService
{
    /**
     * Lấy lịch chiếu (lọc theo rạp, phòng, phim, ngày)
     */
    public function getShowtimes(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Showtime::with([
            'movie:id,title,poster,release_date',
            'room:id,name,cinema_id'
        ])
            // ✅ Lọc theo rạp (cinema_id)
            ->when($filters['cinema_id'] ?? null, function ($query, $cinemaId) {
                $query->whereHas('room', fn($q) => $q->where('cinema_id', $cinemaId));
            })
            // Lọc theo phòng
            ->when($filters['room_id'] ?? null, fn($query, $roomId) => $query->where('room_id', $roomId))
            // Lọc theo phim
            ->when($filters['movie_id'] ?? null, fn($query, $movieId) => $query->where('movie_id', $movieId))
            // Lọc theo ngày chiếu
            ->when($filters['show_date'] ?? null, fn($query, $date) => $query->where('show_date', $date))
            // Lọc theo khoảng ngày
            ->when($filters['from_date'] ?? null, fn($query, $fromDate) => $query->whereDate('show_date', '>=', $fromDate))
            ->when($filters['to_date'] ?? null, fn($query, $toDate) => $query->whereDate('show_date', '<=', $toDate))
            ->orderBy($filters['sort_by'] ?? 'show_date', $filters['sort_order'] ?? 'asc')
            ->orderBy('show_time', 'asc')
            ->paginate($filters['per_page'] ?? 10);
    }

    /**
     * Lấy tất cả các ngày chiếu của một phòng
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
     * Lấy tất cả các ngày chiếu của một rạp (tổng hợp từ các phòng)
     */
    public function getShowDatesByCinema(int $cinemaId): array
    {
        return Showtime::whereHas('room', fn($q) => $q->where('cinema_id', $cinemaId))
            ->select('show_date')
            ->distinct()
            ->orderBy('show_date', 'asc')
            ->pluck('show_date')
            ->toArray();
    }

    /**
     * Lấy tất cả phòng có lịch chiếu
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

    public function getShowtimesByDate(string $date)
{
    return Showtime::with(['movie:id,title,poster', 'room:id,name'])
        ->whereDate('show_date', $date)
        ->orderBy('show_time')
        ->get()
        ->groupBy('movie_id')
        ->map(function ($group) {
            $movie = $group->first()->movie;
            return [
                'movie_id' => $movie->id,
                'movie_title' => $movie->title,
                'poster' => $movie->poster,
                'showtimes' => $group->map(function ($item) {
                    return [
                        'time' => $item->show_time,
                        'format' => $item->format,
                        'language_type' => $item->language_type,
                        'room' => $item->room->name ?? null
                    ];
                })->values()
            ];
        })->values();
}

public function getShowtimesByDateAndLanguage(string $date, string $language)
{
    return Showtime::with(['movie:id,title,poster', 'room:id,name'])
        ->whereDate('show_date', $date)
        ->where('language_type', $language)
        ->orderBy('show_time')
        ->get()
        ->groupBy('movie_id')
        ->map(function ($group) {
            $movie = $group->first()->movie;
            return [
                'movie_id' => $movie->id,
                'movie_title' => $movie->title,
                'poster' => $movie->poster,
                'showtimes' => $group->map(function ($item) {
                    return [
                        'time' => $item->show_time,
                        'format' => $item->format,
                        'room' => $item->room->name ?? null
                    ];
                })->values()
            ];
        })->values();
}

}
