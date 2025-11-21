<?php

namespace App\Http\Controllers;

use App\Http\Requests\SeatStatusUpdateRequest;
use App\Http\Requests\RoomStoreRequest;
use App\Http\Requests\RoomUpdateRequest;
use App\Http\Resources\RoomResource;
use App\Http\Services\Room\RoomService;
use App\Http\Validator\Room\RoomFilterValidator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Showtime;

class RoomController extends Controller
{
    protected RoomService $service;
    protected RoomFilterValidator $validator;

    public function __construct(RoomService $service, RoomFilterValidator $validator)
    {
        $this->service = $service;
        $this->validator = $validator;
    }

    /**
     * Danh sách phòng
     */
    public function index(Request $request): JsonResponse
    {
        $validation = $this->validator->validateWithStatus($request->query());
        if (!$validation['success']) {
            return response()->json($validation, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $filters = $request->only([
            'search',
            'status',
            'sort_by',
            'sort_order',
            'per_page'
        ]);

        $rooms = $this->service->getRooms($filters);

        return response()->json([
            'success' => true,
            'data' => [
                'items' => RoomResource::collection($rooms),
                'pagination' => [
                    'current_page' => $rooms->currentPage(),
                    'per_page'     => $rooms->perPage(),
                    'total'        => $rooms->total(),
                    'last_page'    => $rooms->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * Chi tiết phòng
     */
    public function show(int $id): JsonResponse
    {
        $room = $this->service->getRoomById($id);

        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy phòng chiếu',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'data' => new RoomResource($room),
        ]);
    }

    /**
     * Tạo phòng mới
     */
    public function store(RoomStoreRequest $request): JsonResponse
    {
        $room = $this->service->createRoom($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tạo phòng chiếu thành công',
            'data'    => new RoomResource($room)
        ], 201);
    }

    /**
     * Cập nhật phòng
     */
    public function update(RoomUpdateRequest $request, int $id): JsonResponse
    {
        $room = $this->service->getRoomById($id);

        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy phòng chiếu',
            ], Response::HTTP_NOT_FOUND);
        }

        // Không cho sửa seat_map nếu có suất chiếu tương lai
        if ($request->has('seat_map')) {

            $hasFutureShowtime = Showtime::where('room_id', $room->id)
                ->where('show_date', '>=', now()->format('Y-m-d'))
                ->exists();

            if ($hasFutureShowtime) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể sửa sơ đồ ghế khi phòng đang có suất chiếu trong tương lai.',
                ], 409);
            }
        }


        try {
            $updated = $this->service->updateRoom($room, $request->validated());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật phòng chiếu thành công',
            'data'    => new RoomResource($updated),
        ]);
    }

    /**
     * Xóa phòng
     */
    public function destroy(int $id): JsonResponse
    {
        $room = $this->service->getRoomById($id);

        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy phòng chiếu',
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->service->deleteRoom($room);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Xóa phòng chiếu thành công',
        ]);
    }

    /**
     * Đổi trạng thái phòng
     */
    public function changeStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:active,maintenance,closed',
        ]);

        $room = $this->service->getRoomById($id);
        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy phòng chiếu',
            ], Response::HTTP_NOT_FOUND);
        }


        if ($validated['status'] === 'closed') {
            $hasFuture = Showtime::where('room_id', $id)
                ->where('show_date', '>=', now()->format('Y-m-d'))
                ->exists();

            if ($hasFuture) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể đóng phòng vì đang có suất chiếu chưa diễn ra.',
                ], 409);
            }
        }

        $updated = $this->service->updateRoom($room, [
            'status' => $validated['status']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái phòng chiếu thành công',
            'data' => new RoomResource($updated),
        ]);
    }

    /**
     * Thống kê phòng
     */
    public function statistics(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->service->getStatistics(),
        ]);
    }

    /**
     * Cập nhật trạng thái ghế vật lý + đồng bộ suất chiếu tương lai
     */
    public function updateSeatStatus(SeatStatusUpdateRequest $request, int $roomId, string $seatCode)
    {
        $room = $this->service->getRoomById($roomId);

        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy phòng chiếu',
            ], 404);
        }

        $seatMap = $room->seat_map ?? [];
        $updated = false;

        foreach ($seatMap as &$row) {
            foreach ($row as &$seat) {

                if (is_string($seat)) {
                    if ($seat === $seatCode) {
                        $seat = [
                            'code' => $seat,
                            'type' => 'normal',
                            'status' => $request->status,
                        ];
                        $updated = true;
                        break;
                    }
                }

                if (is_array($seat)) {
                    if (($seat['code'] ?? null) === $seatCode) {
                        $seat['status'] = $request->status;
                        $updated = true;
                        break;
                    }
                }
            }

            if ($updated) break;
        }

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy ghế trong sơ đồ phòng.',
            ], 404);
        }

        // Lưu seat_map
        $room->seat_map = $seatMap;
        $room->save();

        /**
         * Đồng bộ ghế suất chiếu tương lai
         * active  → available
         * broken  → unavailable
         * blocked → unavailable
         */
        $newSeatStatus = $request->status === 'active'
            ? 'available'
            : 'unavailable';

        Showtime::where('room_id', $roomId)
            ->where('show_date', '>=', today())
            ->each(function ($showtime) use ($seatCode, $newSeatStatus) {
                $seat = $showtime->seats()->where('seat_code', $seatCode)->first();
                if ($seat) {
                    $seat->status = $newSeatStatus;
                    $seat->save();
                }
            });

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái ghế thành công.',
            'data' => new RoomResource($room),
        ]);
    }
}
