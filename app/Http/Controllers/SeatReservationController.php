<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\SeatReservation\ConfirmSeatRequest;
use App\Http\Requests\SeatReservation\ReleaseSeatRequest;
use App\Http\Requests\SeatReservation\ReserveSeatRequest;
use App\Http\Services\SeatReservation\SeatReservationService;

class SeatReservationController extends Controller
{
    protected SeatReservationService $service;

    public function __construct(SeatReservationService $service)
    {
        $this->service = $service;
    }

    public function getSeatsByShowtime(int $showtimeId): JsonResponse
    {
        $data = $this->service->getSeatsByShowtime($showtimeId);
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function reserveSeats(ReserveSeatRequest $request): JsonResponse
    {
        $userId = Auth::id();

        try {
            $seats = $this->service->reserveSeats(
                $request->showtime_id,
                $request->seat_ids,
                $userId
            );

            return response()->json([
                'success' => true,
                'message' => 'Giữ ghế thành công. Ghế sẽ được giữ trong 10 phút.',
                'data' => $seats, // trả về danh sách ghế vừa giữ
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        }
    }

    public function confirmBooking(ConfirmSeatRequest $request): JsonResponse
    {
        try {
            $seats = $this->service->confirmBooking(
                $request->showtime_id,
                $request->seat_ids
            );

            return response()->json([
                'success' => true,
                'message' => 'Xác nhận đặt ghế thành công!',
                'data' => $seats, // trả về danh sách ghế vừa đặt
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        }
    }

    public function releaseSeats(ReleaseSeatRequest $request): JsonResponse
    {
        $userId = Auth::id();

        try {
            $seats = $this->service->releaseSeats(
                $request->showtime_id,
                $request->seat_ids,
                $userId
            );

            return response()->json([
                'success' => true,
                'message' => "Hủy giữ ghế thành công.",
                'data' => $seats, // trả về danh sách ghế vừa hủy
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        }
    }
}
