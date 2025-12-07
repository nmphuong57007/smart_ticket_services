<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\SeatResource;
use App\Http\Services\Seat\SeatService;
use Symfony\Component\HttpFoundation\Response;

class SeatController extends Controller
{
    protected SeatService $service;

    public function __construct(SeatService $service)
    {
        $this->service = $service;
    }

    /**
     * LẤY GHẾ THEO SUẤT CHIẾU
     */
    public function getSeatsByShowtime(int $showtimeId): JsonResponse
    {
        $seats = $this->service->getSeatsByShowtime($showtimeId);

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách ghế theo suất chiếu thành công',
            'data'    => SeatResource::collection($seats),
        ]);
    }

    /**
     * CHI TIẾT GHẾ
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
            'data'    => new SeatResource($seat),
        ]);
    }
}
