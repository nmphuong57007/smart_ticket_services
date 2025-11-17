<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\Showtime\ShowtimeService;
use App\Http\Validator\Showtime\ShowtimeFilterValidator;
use App\Http\Requests\Showtime\StoreShowtimeRequest;
use App\Http\Requests\Showtime\UpdateShowtimeRequest;
use App\Http\Resources\ShowtimeResource;
use App\Http\Resources\ShowtimeCollection;

class ShowtimeController extends Controller
{
    protected ShowtimeService $service;
    protected ShowtimeFilterValidator $validator;

    public function __construct(
        ShowtimeService $service,
        ShowtimeFilterValidator $validator
    ) {
        $this->service   = $service;
        $this->validator = $validator;
    }

    /**
     * Danh sách lịch chiếu (lọc + phân trang)
     */
    public function index(Request $request)
    {
        $validation = $this->validator->validateWithStatus($request->query());
        if (!$validation['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors'  => $validation['errors']
            ], 422);
        }

        $filters = $request->only([
            'cinema_id',
            'room_id',
            'movie_id',
            'show_date',
            'from_date',
            'to_date',
            'sort_by',
            'sort_order',
            'per_page'
        ]);

        $showtimes = $this->service->getShowtimes($filters);

        return new ShowtimeCollection($showtimes);
    }

    /**
     * Tạo suất chiếu
     */
    public function store(StoreShowtimeRequest $request)
    {
        $showtime = $this->service->createShowtime($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tạo suất chiếu thành công',
            'data'    => new ShowtimeResource($showtime)
        ], 201);
    }

    /**
     * Cập nhật suất chiếu
     */
    public function update(UpdateShowtimeRequest $request, int $id)
    {
        try {
            $updated = $this->service->updateShowtime($id, $request->validated());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 409); // conflict
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật suất chiếu thành công',
            'data'    => new ShowtimeResource($updated)
        ]);
    }

    /**
     * Xóa suất chiếu
     */
    public function destroy(int $id)
    {
        $this->service->deleteShowtime($id);

        return response()->json([
            'success' => true,
            'message' => 'Xóa suất chiếu thành công'
        ]);
    }

    /**
     * Lấy danh sách ngày chiếu theo phòng
     */
    public function showDates(int $roomId)
    {
        $dates = $this->service->getShowDatesByRoom($roomId);

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách ngày chiếu thành công',
            'data'    => ['dates' => $dates]
        ]);
    }

    /**
     * Lấy các phòng có suất chiếu
     */
    public function rooms()
    {
        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách phòng thành công',
            'data'    => ['rooms' => $this->service->getRoomsWithShowtimes()]
        ]);
    }

    /**
     * Thống kê lịch chiếu
     */
    public function statistics()
    {
        return response()->json([
            'success' => true,
            'message' => 'Lấy thống kê thành công',
            'data'    => $this->service->getShowtimeStatistics()
        ]);
    }
}
