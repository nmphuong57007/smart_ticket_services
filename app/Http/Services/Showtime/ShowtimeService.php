<?php

namespace App\Http\Services\Showtime;

use App\Models\Showtime;
use App\Models\Seat;
use App\Models\Room;
use Illuminate\Support\Carbon;

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
            ->when(
                $filters['cinema_id'] ?? null,
                fn($query, $cinemaId) =>
                $query->whereHas('room', fn($q) => $q->where('cinema_id', $cinemaId))
            )
            ->when($filters['room_id'] ?? null, fn($query, $roomId) => $query->where('room_id', $roomId))
            ->when($filters['movie_id'] ?? null, fn($query, $movieId) => $query->where('movie_id', $movieId))
            ->when($filters['show_date'] ?? null, fn($query, $date) => $query->where('show_date', $date))
            ->when($filters['from_date'] ?? null, fn($query, $fromDate) => $query->whereDate('show_date', '>=', $fromDate))
            ->when($filters['to_date'] ?? null, fn($query, $toDate) => $query->whereDate('show_date', '<=', $toDate))
            ->orderBy($filters['sort_by'] ?? 'show_date', $filters['sort_order'] ?? 'asc')
            ->orderBy('show_time', 'asc')
            ->paginate($filters['per_page'] ?? 10);
    }

    public function getShowDatesByRoom(int $roomId): array
    {
        return Showtime::where('room_id', $roomId)
            ->select('show_date')
            ->distinct()
            ->orderBy('show_date', 'asc')
            ->pluck('show_date')
            ->toArray();
    }

    public function getShowDatesByCinema(int $cinemaId): array
    {
        return Showtime::whereHas('room', fn($q) => $q->where('cinema_id', $cinemaId))
            ->select('show_date')
            ->distinct()
            ->orderBy('show_date', 'asc')
            ->pluck('show_date')
            ->toArray();
    }

    public function getRoomsWithShowtimes(): array
    {
        return Showtime::whereNotNull('room_id')
            ->with('room:id,name,cinema_id')
            ->select('room_id')
            ->distinct()
            ->get()
            ->pluck('room')
            ->filter()
            ->values()
            ->toArray();
    }

    public function getShowtimeStatistics(): array
    {
        return [
            'total_showtimes' => Showtime::count(),
            'total_movies' => Showtime::distinct('movie_id')->count('movie_id'),
            'total_rooms' => Showtime::distinct('room_id')->count('room_id'),
        ];
    }

    public function getShowtimesByDate(string $date)
    {
        return Showtime::with(['movie:id,title,poster', 'room:id,name', 'seats'])
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
                    'showtimes' => $group->map(fn($item) => [
                        'time' => $item->show_time,
                        'format' => $item->format,
                        'language_type' => $item->language_type,
                        'room' => $item->room->name ?? null,
                        'available_seats' => $item->seats->where('status', 'available')->count(),
                        'total_seats' => $item->seats->count()
                    ])->values()
                ];
            })->values();
    }

    public function getShowtimesByDateAndLanguage(string $date, string $language)
    {
        return Showtime::with(['movie:id,title,poster', 'room:id,name', 'seats'])
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
                    'showtimes' => $group->map(fn($item) => [
                        'time' => $item->show_time,
                        'format' => $item->format,
                        'room' => $item->room->name ?? null,
                        'available_seats' => $item->seats->where('status', 'available')->count(),
                        'total_seats' => $item->seats->count()
                    ])->values()
                ];
            })->values();
    }

    public function createShowtime(array $data)
    {
        $showtime = Showtime::create($data);

        $room = Room::find($data['room_id']);
        if ($room && $room->seat_map) {
            $seats = json_decode($room->seat_map, true);
            foreach ($seats as $seat) {
                Seat::create([
                    'showtime_id' => $showtime->id,
                    'seat_code' => $seat['seat_code'],
                    'type' => $seat['type'] ?? 'normal',
                    'status' => 'available'
                ]);
            }
        }

        return $showtime;
    }

    /**
     * Lấy toàn bộ showtimes của một phim, gom theo ngày, tính số ghế còn trống trực tiếp từ DB
     */
    public function getFullShowtimesByMovie(int $movieId)
    {
        // Lấy toàn bộ showtimes của phim, eager load room, movie, seats
        $showtimes = Showtime::with(['movie:id,title,poster', 'room:id,name', 'seats'])
            ->where('movie_id', $movieId)
            ->orderBy('show_date')
            ->orderBy('show_time')
            ->get();

        if ($showtimes->isEmpty()) {
            return [
                'movie_id' => $movieId,
                'movie_title' => null,
                'poster' => null,
                'full_showtimes' => []
            ];
        }

        $fullShowtimes = $showtimes->groupBy(fn($item) => $item->show_date) // show_date là string, không cần format
            ->map(function ($group, $date) {
                return [
                    'date' => $date,
                    'showtimes' => $group->map(function ($item) {
                        return [
                            'time' => $item->show_time,
                            'format' => $item->format,
                            'language_type' => $item->language_type,
                            'room' => $item->room->name ?? null,
                            'available_seats' => $item->seats->where('status', 'available')->count(),
                            'total_seats' => $item->seats->count(),
                        ];
                    })->values()
                ];
            })->values();

        $firstMovie = $showtimes->first()->movie;

        return [
            'movie_id' => $movieId,
            'movie_title' => $firstMovie->title,
            'poster' => $firstMovie->poster,
            'full_showtimes' => $fullShowtimes
        ];
    }
}
