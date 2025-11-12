<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\SeatResource;
use App\Http\Requests\SeatStoreRequest;
use App\Http\Services\Seat\SeatService;
use App\Http\Requests\SeatUpdateRequest;
use App\Http\Requests\SeatChangeStatusRequest;
use Symfony\Component\HttpFoundation\Response;

class SeatController extends Controller
{
    protected SeatService $service;

    public function __construct(SeatService $service)
    {
        $this->service = $service;
    }

    /**
     * LẤY DANH SÁCH GHẾ (FILTER + PAGINATION)
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['room_id', 'cinema_id', 'type', 'status', 'search', 'per_page']);
        $seats = $this->service->getSeats($filters);

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách ghế thành công',
            'data' => [
                'items' => SeatResource::collection($seats),
                'pagination' => [
                    'current_page' => $seats->currentPage(),
                    'per_page' => $seats->perPage(),
                    'total' => $seats->total(),
                    'last_page' => $seats->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * LẤY CHI TIẾT 1 GHẾ
     */
    public function show(int $id): JsonResponse
    {
        $seat = $this->service->getSeatById($id);
        if (!$seat) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy ghế',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lấy thông tin ghế thành công',
            'data' => new SeatResource($seat),
        ]);
    }

    /**
     * TẠO GHẾ MỚI
     */
    public function store(SeatStoreRequest $request): JsonResponse
    {
        $seat = $this->service->createSeat($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tạo ghế thành công',
            'data' => new SeatResource($seat),
        ], Response::HTTP_CREATED);
    }

    /**
     * CẬP NHẬT GHẾ
     */
    public function update(SeatUpdateRequest $request, int $id): JsonResponse
    {
        $seat = $this->service->getSeatById($id);
        if (!$seat) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy ghế',
            ], Response::HTTP_NOT_FOUND);
        }

        $updated = $this->service->updateSeat($seat, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật ghế thành công',
            'data' => new SeatResource($updated),
        ]);
    }

    /**
     * XOÁ GHẾ
     */
    public function destroy(int $id): JsonResponse
    {
        $seat = $this->service->getSeatById($id);
        if (!$seat) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy ghế',
            ], Response::HTTP_NOT_FOUND);
        }

        $this->service->deleteSeat($seat);

        return response()->json([
            'success' => true,
            'message' => 'Xóa ghế thành công',
        ]);
    }

    /**
     * LẤY DANH SÁCH GHẾ THEO PHÒNG
     */
    public function getSeatsByRoom(int $roomId): JsonResponse
    {
        $seats = $this->service->getSeatsByRoom($roomId);

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách ghế theo phòng thành công',
            'data' => SeatResource::collection($seats),
        ]);
    }

    /**
     * ĐỔI TRẠNG THÁI GHẾ (available, maintenance, disabled)
     */
    public function changeStatus(SeatChangeStatusRequest $request, int $id): JsonResponse
    {
        $seat = $this->service->getSeatById($id);
        if (!$seat) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy ghế',
            ], Response::HTTP_NOT_FOUND);
        }

        $updated = $this->service->changeStatus($seat, $request->validated()['status']);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái ghế thành công',
            'data' => new SeatResource($updated),
        ]);
    }
}
