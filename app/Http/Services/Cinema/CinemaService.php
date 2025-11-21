<?php

namespace App\Http\Services\Cinema;

use App\Models\Cinema;
use App\Models\Showtime;
use App\Models\Room;
use Illuminate\Support\Facades\DB;

class CinemaService
{
    /**
     * Lấy thông tin rạp duy nhất (ID = 1)
     */
    public function getCinema()
    {
        return Cinema::with('rooms')->find(1);
    }

    /**
     * Lấy danh sách phòng của rạp duy nhất
     */
    public function getRooms()
    {
        return Room::where('cinema_id', 1)
            ->orderBy('name')
            ->get();
    }

    /**
     * Lấy lịch chiếu của rạp duy nhất
     * (lấy showtimes của tất cả rooms thuộc cinema_id = 1)
     */
    public function getShowtimes($date = null)
    {
        // Lấy danh sách rooms thuộc rạp duy nhất
        $roomIds = Room::where('cinema_id', 1)->pluck('id');

        return Showtime::with([
            'movie:id,title,poster,format,language',
            'room:id,name'
        ])
            ->whereIn('room_id', $roomIds)
            ->when($date, fn($q) => $q->where('show_date', $date))
            ->orderBy('show_date')
            ->orderBy('show_time')
            ->get();
    }

    /**
     * Thống kê tổng quan rạp duy nhất
     */
    public function getCinemaStatistics(): array
    {
        $totalRooms      = Room::where('cinema_id', 1)->count();

        return [
            'cinema_id'        => 1,
            'total_rooms'      => $totalRooms,
            'total_showtimes'  => Showtime::whereIn(
                'room_id',
                Room::where('cinema_id', 1)->pluck('id')
            )->count(),
        ];
    }
}
