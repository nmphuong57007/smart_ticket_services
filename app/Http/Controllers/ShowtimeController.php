<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\Showtime\ShowtimeService;
use App\Http\Validator\Showtime\ShowtimeFilterValidator;
use App\Http\Requests\Showtime\StoreShowtimeRequest;
use App\Http\Requests\Showtime\UpdateShowtimeRequest;
use App\Http\Resources\ShowtimeResource;

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

        return response()->json([
            'items' => ShowtimeResource::collection($showtimes->items()),
            'pagination' => [
                'page'      => $showtimes->currentPage(),
                'per_page'  => $showtimes->perPage(),
                'total'     => $showtimes->total(),
                'last_page' => $showtimes->lastPage(),
            ]
        ]);
    }


    /**
     * Tự động decode message JSON khi service throw exception
     */
    private function decodeException(\Exception $e)
    {
        $msg = json_decode($e->getMessage(), true);

        // Trường hợp message KHÔNG phải JSON → trả về mặc định
        if (!is_array($msg)) {
            return [
                'message'  => $e->getMessage(),
                'conflict' => null
            ];
        }

        // Trường hợp là JSON hợp lệ (message + conflict)
        return [
            'message'  => $msg['message'] ?? 'Có lỗi xảy ra',
            'conflict' => $msg['conflict'] ?? null
        ];
    }


    /**
     * Tạo suất chiếu
     */
    public function store(StoreShowtimeRequest $request)
    {
        try {
            $showtime = $this->service->createShowtime($request->validated());
        } catch (\Exception $e) {

            $err = $this->decodeException($e);

            return response()->json([
                'success'  => false,
                'message'  => $err['message'],
                'conflict' => $err['conflict']
            ], 409);
        }

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

            $err = $this->decodeException($e);

            return response()->json([
                'success'  => false,
                'message'  => $err['message'],
                'conflict' => $err['conflict']
            ], 409);
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
        try {
            $this->service->deleteShowtime($id);
        } catch (\Exception $e) {

            $err = $this->decodeException($e);

            return response()->json([
                'success'  => false,
                'message'  => $err['message'],
                'conflict' => $err['conflict']
            ], 409);
        }

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
