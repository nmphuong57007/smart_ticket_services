<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\SeatReservation\ConfirmSeatRequest;
use App\Http\Requests\SeatReservation\ReleaseSeatRequest;
use App\Http\Requests\SeatReservation\ReserveSeatRequest;
use App\Http\Services\SeatReservation\SeatReservationService;
use App\Http\Resources\SeatReservationResource;

class SeatReservationController extends Controller
{
    protected SeatReservationService $service;

    public function __construct(SeatReservationService $service)
    {
        $this->service = $service;
    }

    /**
     * Lấy danh sách ghế theo suất chiếu kèm trạng thái
     */
    public function getSeatsByShowtime(int $showtimeId): JsonResponse
    {
        $data = $this->service->getSeatsByShowtime($showtimeId);

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách ghế theo suất chiếu thành công.',
            'data' => $data,
        ]);
    }

    /**
     * Giữ ghế tạm thời
     */
    public function reserveSeats(ReserveSeatRequest $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn cần đăng nhập để giữ ghế.',
            ], 401);
        }

        try {
            $seats = $this->service->reserveSeats(
                $request->showtime_id,
                $request->seat_ids,
                $user->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Giữ ghế thành công. Ghế sẽ được giữ trong 10 phút.',
                'data' => SeatReservationResource::collection($seats),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        }
    }

    /**
     * Xác nhận đặt ghế
     */
    public function confirmBooking(ConfirmSeatRequest $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn cần đăng nhập để xác nhận đặt ghế.',
            ], 401);
        }

        try {
            $seats = $this->service->confirmBooking(
                $request->showtime_id,
                $request->seat_ids,
                $user->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Xác nhận đặt ghế thành công!',
                'data' => SeatReservationResource::collection($seats),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        }
    }

    /**
     * Hủy giữ ghế
     */
    public function releaseSeats(ReleaseSeatRequest $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn cần đăng nhập để hủy giữ ghế.',
            ], 401);
        }

        try {
            $result = $this->service->releaseSeats(
                $request->showtime_id,
                $request->seat_ids,
                $user->id
            );

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => SeatReservationResource::collection($result['released_seats']),
                'failed_seat_ids' => $result['failed_seat_ids'],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        }
    }

    /**
     * Lấy danh sách đặt ghế của user hiện tại
     */
    public function myReservations(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn cần đăng nhập để xem đặt chỗ của mình.',
            ], 401);
        }

        $reservations = $this->service->getMyReservations($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách đặt chỗ của bạn thành công.',
            'data' => SeatReservationResource::collection($reservations),
        ]);
    }

    /**
     * Lấy thống kê ghế theo suất chiếu
     */
    public function seatStats(int $showtimeId): JsonResponse
    {
        $stats = $this->service->getSeatStats($showtimeId);

        return response()->json([
            'success' => true,
            'message' => 'Lấy thống kê ghế theo suất chiếu thành công.',
            'data' => $stats,
        ]);
    }
}
