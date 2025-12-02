<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingRequest;
use App\Http\Services\Booking\BookingService;
use App\Models\Booking;
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
                'data' => $booking
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
     * Chi tiết booking (dùng để xem vé)
     */
    public function show($id)
    {
        $booking = Booking::with([
            'tickets.seat',
            'products.product',
            'showtime.movie',
            'showtime.room',
        ])->find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy booking',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $booking
        ]);
    }

    /**
     * Lấy danh sách booking của user
     */
    public function myBookings()
    {
        $userId = Auth::id();

        $bookings = Booking::where('user_id', $userId)
            ->with([
                'tickets.seat',
                'showtime.movie',
                'showtime.room'
            ])
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bookings
        ]);
    }

    /**
     * (Admin/Staff) – Lấy danh sách toàn bộ bookings
     */
    public function index(Request $request)
    {
        $status = $request->query('status');

        $query = Booking::with(['user', 'showtime.movie', 'showtime.room']);

        if ($status) {
            $query->where('payment_status', $status);
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('id', 'desc')->paginate(15)
        ]);
    }
}
