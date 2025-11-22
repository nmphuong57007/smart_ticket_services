<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\Cinema\CinemaService;
use App\Http\Resources\CinemaResource;

class CinemaController extends Controller
{
    protected CinemaService $cinemaService;

    public function __construct(CinemaService $cinemaService)
    {
        $this->cinemaService = $cinemaService;
    }

    /**
     * Lấy thông tin rạp duy nhất (ID = 1)
     */
    public function cinema()
    {
        try {
            $cinema = $this->cinemaService->getCinema();

            if (!$cinema) {
                return response([
                    'success' => false,
                    'message' => 'Không tìm thấy rạp.',
                ], 404);
            }

            return response([
                'success' => true,
                'message' => 'Lấy thông tin rạp thành công',
                'data' => new CinemaResource($cinema),
            ]);
        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Lỗi khi lấy thông tin rạp',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy danh sách phòng thuộc rạp duy nhất
     */
    public function rooms()
    {
        try {
            $rooms = $this->cinemaService->getRooms();

            return response([
                'success' => true,
                'message' => 'Lấy danh sách phòng thành công',
                'data' => $rooms,
            ]);
        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách phòng',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy lịch chiếu của rạp
     * (có thể lọc theo ngày: /cinema/showtimes?date=2025-01-01)
     */
    public function showtimes(Request $request)
    {
        try {
            $date = $request->query('date'); // optional

            $showtimes = $this->cinemaService->getShowtimes($date);

            return response([
                'success' => true,
                'message' => 'Lấy lịch chiếu thành công',
                'data' => $showtimes,
            ]);
        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Lỗi khi lấy lịch chiếu',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Thống kê tổng quan rạp duy nhất
     */
    public function statistics()
    {
        try {
            $stats = $this->cinemaService->getCinemaStatistics();

            return response([
                'success' => true,
                'message' => 'Lấy thống kê thành công',
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Lỗi khi lấy thống kê',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
