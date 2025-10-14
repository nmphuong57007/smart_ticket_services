<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\Showtime\ShowtimeService;
use App\Http\Validator\Showtime\ShowtimeFilterValidator;

class ShowtimeController extends Controller
{
    protected ShowtimeService $showtimeService;
    protected ShowtimeFilterValidator $showtimeFilterValidator;

    public function __construct(
        ShowtimeService $showtimeService,
        ShowtimeFilterValidator $showtimeFilterValidator
    ) {
        $this->showtimeService = $showtimeService;
        $this->showtimeFilterValidator = $showtimeFilterValidator;
    }

    /**
     * Lấy danh sách lịch chiếu (lọc theo rạp, phòng, phim, ngày...)
     */
    public function index(Request $request)
    {
        try {
            // Validate query parameters
            $validationResult = $this->showtimeFilterValidator->validateWithStatus($request->query());
            if (!$validationResult['success']) {
                return response([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validationResult['errors']
                ], 422);
            }

            // Gom filters
            $filters = [
                'cinema_id'  => $request->query('cinema_id'),
                'room_id'    => $request->query('room_id'),
                'movie_id'   => $request->query('movie_id'),
                'show_date'  => $request->query('show_date'),
                'from_date'  => $request->query('from_date'),
                'to_date'    => $request->query('to_date'),
                'sort_by'    => $request->query('sort_by', 'show_date'),
                'sort_order' => $request->query('sort_order', 'asc'),
                'per_page'   => $request->query('per_page', 15)
            ];

            $showtimes = $this->showtimeService->getShowtimes($filters);

            // Trả về dữ liệu có phân trang
            return response([
                'success' => true,
                'message' => 'Lấy danh sách lịch chiếu thành công',
                'data' => [
                    'showtimes' => $showtimes->items(),
                    'pagination' => [
                        'current_page' => $showtimes->currentPage(),
                        'last_page' => $showtimes->lastPage(),
                        'per_page' => $showtimes->perPage(),
                        'total' => $showtimes->total(),
                        'from' => $showtimes->firstItem(),
                        'to' => $showtimes->lastItem()
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Lấy danh sách lịch chiếu thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy tất cả các ngày chiếu của một phòng
     */
    public function showDates(int $roomId)
    {
        try {
            $dates = $this->showtimeService->getShowDatesByRoom($roomId);

            return response([
                'success' => true,
                'message' => 'Lấy danh sách ngày chiếu thành công',
                'data' => ['dates' => $dates]
            ], 200);

        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Lấy danh sách ngày chiếu thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách tất cả phòng có lịch chiếu
     */
    public function rooms()
    {
        try {
            $rooms = $this->showtimeService->getRoomsWithShowtimes();

            return response([
                'success' => true,
                'message' => 'Lấy danh sách phòng chiếu thành công',
                'data' => ['rooms' => $rooms]
            ], 200);

        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Lấy danh sách phòng chiếu thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy thống kê lịch chiếu
     */
    public function statistics()
    {
        try {
            $stats = $this->showtimeService->getShowtimeStatistics();

            return response([
                'success' => true,
                'message' => 'Lấy thống kê lịch chiếu thành công',
                'data' => ['statistics' => $stats]
            ], 200);

        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Lấy thống kê lịch chiếu thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
