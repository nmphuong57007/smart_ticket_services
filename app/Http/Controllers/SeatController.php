<?php

namespace App\Http\Controllers;

use App\Http\Requests\SeatStoreRequest;
use App\Http\Requests\SeatUpdateRequest;
use App\Http\Resources\SeatResource;
use App\Http\Services\Seat\SeatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SeatController extends Controller
{
    protected SeatService $service;

    public function __construct(SeatService $service)
    {
        $this->service = $service;
    }

    /**
     * Lấy danh sách ghế (có filter & pagination)
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['room_id', 'cinema_id', 'type', 'status', 'search', 'per_page']);
        $seats = $this->service->getSeats($filters);

        return response()->json([
            'success' => true,
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
     * Lấy chi tiết ghế theo ID
     */
    public function show(int $id): JsonResponse
    {
        $seat = $this->service->getSeatById($id);

        if (!$seat) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy ghế'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'data' => new SeatResource($seat)
        ]);
    }

    /**
     * Tạo mới ghế
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
     * Cập nhật thông tin ghế
     */
    public function update(SeatUpdateRequest $request, int $id): JsonResponse
    {
        $seat = $this->service->getSeatById($id);

        if (!$seat) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy ghế'
            ], Response::HTTP_NOT_FOUND);
        }

        $updated = $this->service->updateSeat($seat, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật ghế thành công',
            'data' => new SeatResource($updated)
        ]);
    }

    /**
     * Xóa ghế
     */
    public function destroy(int $id): JsonResponse
    {
        $seat = $this->service->getSeatById($id);

        if (!$seat) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy ghế'
            ], Response::HTTP_NOT_FOUND);
        }

        $this->service->deleteSeat($seat);

        return response()->json([
            'success' => true,
            'message' => 'Xóa ghế thành công'
        ]);
    }

}
