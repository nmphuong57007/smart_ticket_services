<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\Cinema\CinemaService;

class CinemaController extends Controller
{
    protected CinemaService $cinemaService;

    public function __construct(CinemaService $cinemaService)
    {
        $this->cinemaService = $cinemaService;
    }

    /**
     * Lấy danh sách rạp (có phân trang + filter tên)
     */
    public function index(Request $request)
    {
        try {
            $filters = [
                'name' => $request->query('name'),
                'per_page' => $request->query('per_page', 10)
            ];

            $cinemas = $this->cinemaService->getCinemas($filters);

            return response([
                'success' => true,
                'message' => 'Lấy danh sách rạp chiếu thành công',
                'data' => [
                    'cinemas' => $cinemas->items(),
                    'pagination' => [
                        'current_page' => $cinemas->currentPage(),
                        'last_page' => $cinemas->lastPage(),
                        'total' => $cinemas->total(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Lấy danh sách rạp thất bại',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy chi tiết rạp theo ID (kèm danh sách phòng)
     */
    public function show($id)
    {
        try {
            $cinema = $this->cinemaService->getCinemaById($id);

            if (!$cinema) {
                return response([
                    'success' => false,
                    'message' => 'Không tìm thấy rạp chiếu',
                ], 404);
            }

            return response([
                'success' => true,
                'message' => 'Lấy thông tin rạp chiếu thành công',
                'data' => $cinema,
            ]);
        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Lấy thông tin rạp thất bại',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy danh sách phòng của rạp
     */
    public function rooms($cinemaId)
    {
        try {
            $rooms = $this->cinemaService->getRoomsByCinema($cinemaId);

            return response([
                'success' => true,
                'message' => 'Lấy danh sách phòng của rạp thành công',
                'data' => $rooms,
            ]);
        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Lấy danh sách phòng thất bại',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy toàn bộ lịch chiếu của rạp (tổng hợp từ các phòng)
     */
    public function showtimes($cinemaId)
    {
        try {
            $showtimes = $this->cinemaService->getShowtimesByCinema($cinemaId);

            return response([
                'success' => true,
                'message' => 'Lấy lịch chiếu của rạp thành công',
                'data' => $showtimes,
            ]);
        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Lấy lịch chiếu thất bại',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Thống kê tổng quan
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
                'message' => 'Lấy thống kê thất bại',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
