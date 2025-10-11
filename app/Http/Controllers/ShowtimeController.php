<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\Showtime\ShowtimeService;

class ShowtimeController extends Controller
{
    protected ShowtimeService $showtimeService;

    public function __construct(ShowtimeService $showtimeService)
    {
        $this->showtimeService = $showtimeService;
    }

    /**
     * Lấy danh sách lịch chiếu
     * Có filter theo phòng, phim, ngày và phân trang
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'room_id',
            'movie_id',
            'show_date',
            'from_date',
            'to_date',
            'per_page'
        ]);

        $showtimes = $this->showtimeService->getShowtimes($filters);

        return response()->json([
            'success' => true,
            'data' => $showtimes->items(),
            'pagination' => [
                'current_page' => $showtimes->currentPage(),
                'per_page' => $showtimes->perPage(),
                'total' => $showtimes->total(),
                'last_page' => $showtimes->lastPage(),
            ]
        ]);
    }

    /**
     * Lấy tất cả các ngày chiếu của một phòng
     */
    public function showDates(int $roomId)
    {
        $dates = $this->showtimeService->getShowDatesByRoom($roomId);

        return response()->json([
            'success' => true,
            'data' => $dates
        ]);
    }

    /**
     * Lấy danh sách tất cả phòng có lịch chiếu
     */
    public function rooms()
    {
        $rooms = $this->showtimeService->getRoomsWithShowtimes();

        return response()->json([
            'success' => true,
            'data' => $rooms
        ]);
    }

    /**
     * Lấy thống kê showtime
     */
    public function statistics()
    {
        $stats = $this->showtimeService->getShowtimeStatistics();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
