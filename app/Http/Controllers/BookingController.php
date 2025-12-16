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

            $booking = $this->service->createBooking(
                $request->validated(),
                $user->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Tạo đơn giữ chỗ thành công, vui lòng thanh toán trong 10 phút.',
                'data'    => new BookingDetailResource($booking)
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể tạo đơn giữ chỗ.',
                'error'   => $e->getMessage()
            ], 422);
        }
    }


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
                'message' => 'Không tìm thấy đơn vé',
            ], 404);
        }


        $user = $request->user();
        if ($user->role === 'customer' && $booking->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền truy cập đơn vé này',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Chi tiết đơn vé',
            'data'    => new BookingDetailResource($booking)
        ]);
    }


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
            'message' => 'Danh sách đơn vé của bạn',
            'data'    => BookingListResource::collection($bookings)
        ]);
    }

    /**
     * ADMIN / STAFF – danh sách booking (DÙNG SERVICE)
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'booking_id',
            'booking_code',
            'qr_code',
            'user_name',
            'status',
            'per_page',
            'sort_by',
            'sort_order',
        ]);


        $bookings = $this->service->paginateBookings($filters);

        if ($bookings->isEmpty()) {
            $message = 'Không tìm thấy đơn vé phù hợp với điều kiện lọc';
        } elseif (!empty(array_filter($filters))) {
            $message = 'Danh sách đơn vé theo bộ lọc';
        } else {
            $message = 'Danh sách toàn bộ đơn vé';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => BookingListResource::collection($bookings),
            'meta'    => [
                'current_page' => $bookings->currentPage(),
                'last_page'    => $bookings->lastPage(),
                'per_page'     => $bookings->perPage(),
                'total'        => $bookings->total(),
            ]
        ]);
    }
}
