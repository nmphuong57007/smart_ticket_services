<?php

namespace App\Http\Services\Showtime;

use App\Models\Room;
use App\Models\Showtime;

class ShowtimeService
{
    /**
     * Lấy danh sách lịch chiếu + lọc + sort + paginate
     */
    public function getShowtimes(array $filters = [])
    {
        return Showtime::with([
            'movie:id,title,poster,release_date',
            'room:id,name,cinema_id',
            'cinema:id,name'
        ])
            ->when($filters['cinema_id'] ?? null, fn($q, $v) => $q->where('cinema_id', $v))
            ->when($filters['room_id'] ?? null, fn($q, $v) => $q->where('room_id', $v))
            ->when($filters['movie_id'] ?? null, fn($q, $v) => $q->where('movie_id', $v))
            ->when($filters['show_date'] ?? null, fn($q, $v) => $q->where('show_date', $v))
            ->when($filters['from_date'] ?? null, fn($q, $v) => $q->whereDate('show_date', '>=', $v))
            ->when($filters['to_date'] ?? null, fn($q, $v) => $q->whereDate('show_date', '<=', $v))
            ->orderBy($filters['sort_by'] ?? 'show_date', $filters['sort_order'] ?? 'asc')
            ->orderBy('show_time', 'asc')
            ->paginate($filters['per_page'] ?? 10);
    }


    /**
     * Tạo lịch chiếu mới
     */
    public function createShowtime(array $data)
    {
        if (!isset($data['cinema_id'])) {
            $data['cinema_id'] = Room::find($data['room_id'])->cinema_id ?? null;
        }

        return Showtime::create($data);
    }


    /**
     * Cập nhật lịch chiếu
     */
    public function updateShowtime(int $id, array $data)
    {
        $showtime = Showtime::findOrFail($id);

        $exists = Showtime::where('room_id', $data['room_id'])
            ->where('show_date', $data['show_date'])
            ->where('show_time', $data['show_time'])
            ->where('id', '<>', $id)
            ->exists();

        if ($exists) {
            throw new \Exception("Giờ chiếu này đã tồn tại trong phòng.");
        }

        // Auto update cinema_id khi đổi room
        if (isset($data['room_id'])) {
            $data['cinema_id'] = Room::find($data['room_id'])->cinema_id ?? null;
        }

        $showtime->update($data);

        return $showtime;
    }

    /**
     * Xóa lịch chiếu
     * (KHÔNG dùng tickets – KHÔNG đụng seats)
     */
    public function deleteShowtime(int $id)
    {
        $showtime = Showtime::findOrFail($id);
        $showtime->delete();
        return true;
    }

    /**
     * Danh sách ngày chiếu theo phòng
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
     * Danh sách phòng đang có suất chiếu
     */
    public function getRoomsWithShowtimes(): array
    {
        return Showtime::with('room:id,name,cinema_id')
            ->select('room_id')
            ->distinct()
            ->get()
            ->map(fn($item) => $item->room)
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Thống kê cơ bản
     */
    public function getShowtimeStatistics(): array
    {
        return [
            'total_showtimes' => Showtime::count(),
            'total_movies'    => Showtime::distinct('movie_id')->count('movie_id'),
            'total_rooms'     => Showtime::distinct('room_id')->count('room_id'),
        ];
    }
}
