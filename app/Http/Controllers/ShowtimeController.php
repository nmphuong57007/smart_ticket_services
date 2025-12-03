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
            'success' => true,
            'message' => 'Lấy danh sách lịch chiếu thành công',
            'data' => [
                'items' => ShowtimeResource::collection($showtimes),
                'pagination' => [
                    'page'      => $showtimes->currentPage(),
                    'per_page'  => $showtimes->perPage(),
                    'total'     => $showtimes->total(),
                    'last_page' => $showtimes->lastPage(),
                ]
            ]
        ]);
    }

    // Chi tiết suất chiếu
    public function show(int $id)
    {
        $showtime = $this->service->getShowtimeById($id);

        if (!$showtime) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy suất chiếu'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lấy chi tiết suất chiếu thành công',
            'data' => new ShowtimeResource($showtime)
        ]);
    }


    /**
     * Decode error service throw
     */
    private function decodeException(\Exception $e)
    {
        $msg = json_decode($e->getMessage(), true);

        if (!is_array($msg)) {
            return [
                'message'  => $e->getMessage(),
                'conflict' => null
            ];
        }

        return [
            'message'  => $msg['message'] ?? $e->getMessage(),
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
     * Lấy sơ đồ ghế của suất chiếu (public)
     */
    public function seats(int $id)
    {
        $showtime = $this->service->getShowtimeById($id);

        if (!$showtime) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy suất chiếu'
            ], 404);
        }

        $room = $showtime->room;
        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Suất chiếu không thuộc phòng nào'
            ], 404);
        }

        $seatMap = $room->seat_map ?? [];
        $seats   = $showtime->seats()->get()->keyBy('seat_code');

        $result = [];

        foreach ($seatMap as $row) {
            $rowData = [];

            foreach ($row as $seat) {

                if (is_string($seat)) {
                    $code = $seat;
                    $physical = [
                        'code'   => $code,
                        'type'   => 'normal',
                        'status' => 'active'
                    ];
                } else {
                    $code = $seat['code'];
                    $physical = [
                        'code'   => $seat['code'],
                        'type'   => $seat['type'] ?? 'normal',
                        'status' => $seat['status'] ?? 'active'
                    ];
                }

                $seatShowtime = $seats[$code] ?? null;

                $rowData[] = [
                    'id'              => $seatShowtime->id ?? null,
                    'code'            => $code,
                    'type'            => $physical['type'],
                    'physical_status' => $physical['status'],
                    'status'          => $seatShowtime->status ?? 'available',
                    'price'           => $seatShowtime->price ?? $showtime->price,
                ];
            }

            $result[] = $rowData;
        }

        return response()->json([
            'success' => true,
            'message' => 'Lấy sơ đồ ghế thành công',
            'data'    => [
                'showtime_id' => $showtime->id,
                'room_id'     => $room->id,
                'seat_map'    => $result
            ]
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
     * Thống kê lịch chiếu tổng
     */
    public function statistics()
    {
        return response()->json([
            'success' => true,
            'message' => 'Lấy thống kê thành công',
            'data'    => $this->service->getShowtimeStatistics()
        ]);
    }

    /**
     * Thống kê lịch chiếu theo ngày
     */
    public function statisticsByDate(Request $request)
    {
        $request->validate([
            'date' => 'required|date'
        ]);

        $date = $request->query('date');

        $stats = $this->service->getStatisticsByDate($date);

        return response()->json([
            'success' => true,
            'message' => 'Lấy thống kê lịch chiếu theo ngày thành công',
            'data'    => $stats
        ]);
    }
}
