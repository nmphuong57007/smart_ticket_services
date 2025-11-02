<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoomStoreRequest;
use App\Http\Requests\RoomUpdateRequest;
use App\Http\Resources\RoomResource;
use App\Http\Services\Room\RoomService;
use App\Http\Validator\Room\RoomFilterValidator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

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
     * Lấy danh sách phòng chiếu (phân trang, tìm kiếm, lọc)
     */
    public function index(Request $request): JsonResponse
    {
        $validation = $this->validator->validateWithStatus($request->query());
        if (!$validation['success']) {
            return response()->json($validation, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $filters = $request->only([
            'search',
            'cinema_id',
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
                    'per_page' => $rooms->perPage(),
                    'total' => $rooms->total(),
                    'last_page' => $rooms->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * Lấy chi tiết 1 phòng
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
            'data' => new RoomResource($room->load('cinema')),
        ]);
    }

    /**
     * Tạo mới phòng chiếu
     */
    public function store(RoomStoreRequest $request): JsonResponse
    {
        try {
            $room = $this->service->createRoom($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Tạo phòng chiếu thành công',
                'data' => new RoomResource($room),
            ], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo phòng chiếu: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Cập nhật thông tin phòng chiếu
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

        $updatedRoom = $this->service->updateRoom($room, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật phòng chiếu thành công',
            'data' => new RoomResource($updatedRoom),
        ]);
    }

    /**
     * Xóa phòng chiếu
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

        $this->service->deleteRoom($room);

        return response()->json([
            'success' => true,
            'message' => 'Xóa phòng chiếu thành công',
        ]);
    }

    /**
     * Lấy danh sách phòng theo rạp
     */
    public function byCinema(int $cinemaId): JsonResponse
    {
        $rooms = $this->service->getRoomsByCinema($cinemaId);

        return response()->json([
            'success' => true,
            'data' => RoomResource::collection($rooms),
        ]);
    }

    /**
     * Cập nhật trạng thái phòng chiếu
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

        $updatedRoom = $this->service->updateRoom($room, ['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái phòng chiếu thành công',
            'data' => new RoomResource($updatedRoom),
        ]);
    }

    /**
     * Thống kê tổng quan phòng chiếu
     */
    public function statistics(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->getStatistics(),
        ]);
    }
}
