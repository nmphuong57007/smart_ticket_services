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
     * Tạo đơn đặt vé (chỉ giữ chỗ + tạo booking pending)
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
                'message' => 'Tạo đơn giữ chỗ thành công, vui lòng thanh toán trong 10 phút.',
                'data' => new BookingDetailResource($booking)
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể tạo đơn giữ chỗ.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Chi tiết booking
     */
    public function show(Request $request, $id)
    {
        $booking = Booking::with([
            'user',
            'payments',
            'ticket',
            'bookingSeats.seat',
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

        // CUSTOMER chỉ được xem booking của họ
        $user = $request->user();

        if ($user->role === 'customer' && $booking->user_id != $user->id) {
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
     * USER xem danh sách booking của họ
     */
    public function myBookings()
    {
        $userId = Auth::id();

        $bookings = Booking::where('user_id', $userId)
            ->with([
                'payments',
                'ticket',
                'bookingSeats.seat',
                'products.product',
                'showtime.movie',
                'showtime.room.cinema',
            ])
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => BookingListResource::collection($bookings)
        ]);
    }

    /**
     * ADMIN / STAFF xem & LỌC toàn bộ booking
     */
    public function index(Request $request)
    {
        $query = Booking::with([
            'user',
            'payments',
            'ticket',
            'bookingSeats.seat',
            'showtime.movie',
            'showtime.room.cinema'
        ]);

        // ===== ADMIN FILTER =====

        // 1. Lọc theo booking_id
        if ($request->filled('booking_id')) {
            $query->where('id', $request->booking_id);
        }

        // 2. Lọc theo booking_code
        if ($request->filled('booking_code')) {
            $query->where('booking_code', 'like', '%' . $request->booking_code . '%');
        }

        // 3. Lọc theo QR code (decode → booking_id)
        if ($request->filled('qr_code')) {
            $json = base64_decode($request->qr_code, true);
            $data = json_decode($json, true);

            if (is_array($data) && isset($data['booking_id'])) {
                $query->where('id', $data['booking_id']);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'QR không hợp lệ',
                ], 422);
            }
        }

        // 4. Lọc theo tên người dùng
        if ($request->filled('user_name')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('fullname', 'like', '%' . $request->user_name . '%');
            });
        }

        // 5. Lọc theo trạng thái booking
        if ($request->filled('status')) {
            $query->where('booking_status', $request->status);
        }

        $bookings = $query
            ->orderBy('id', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => BookingListResource::collection($bookings)
        ]);
    }
}
