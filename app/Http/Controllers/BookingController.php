<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingRequest;
use App\Http\Services\Booking\BookingService;
use App\Models\Booking;
use App\Http\Resources\BookingListResource;
use App\Http\Resources\BookingDetailResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class BookingController extends Controller
{
    protected BookingService $service;

    public function __construct(BookingService $service)
    {
        $this->service = $service;
    }

    /**
     * Tạo đơn đặt vé
     */
    public function store(BookingRequest $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn cần đăng nhập để đặt vé.',
                ], 401);
            }

            $booking = $this->service->createBooking($request->validated(), $user->id);

            return response()->json([
                'success' => true,
                'message' => 'Đặt vé thành công',
                'data' => new BookingDetailResource($booking)
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đặt vé thất bại',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Chi tiết 1 booking
     */
    public function show(Request $request, $id)
    {
        $booking = Booking::with([
            'user',
            'payments',
            'tickets.seat',
            'products.product',
            'showtime.movie',
            'showtime.room.cinema'
        ])->find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy booking',
            ], 404);
        }

        // CUSTOMER chỉ xem của họ
        $user = $request->user();

        if ($user->role === 'customer' && $booking->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền truy cập booking này',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new BookingDetailResource($booking)
        ]);
    }

    /**
     * CUSTOMER xem danh sách booking của họ
     */
    public function myBookings()
    {
        $userId = Auth::id();

        $bookings = Booking::where('user_id', $userId)
            ->with([
                'payments',
                'tickets.seat',
                'products.product',     // ⭐ LUÔN GIỮ NGUYÊN
                'showtime.movie',
                'showtime.room.cinema',
            ])
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => BookingDetailResource::collection($bookings)
        ]);
    }

    /**
     * ADMIN / STAFF xem toàn bộ booking
     */
    public function index(Request $request)
    {
        $status = $request->query('status');

        $query = Booking::with([
            'user',
            'payments',
            'showtime.movie',
            'showtime.room.cinema'
        ]);

        if ($status) {
            $query->where('payment_status', $status);
        }

        $bookings = $query->orderBy('id', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => BookingListResource::collection($bookings)
        ]);
    }
}
