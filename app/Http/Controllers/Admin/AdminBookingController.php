<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use App\Http\Resources\BookingResource;

class AdminBookingController extends Controller
{
    /**
     * Lấy danh sách tất cả booking (admin)
     */
    public function index(Request $request)
    {
        $query = Booking::with([
            'user',
            'showtime.movie',
            'showtime.room',
            'tickets.seat',
            'products.product'
        ]);

        // Lọc theo trạng thái thanh toán
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Lọc trạng thái đơn hàng
        if ($request->has('booking_status')) {
            $query->where('booking_status', $request->booking_status);
        }

        // Lọc theo user_id (nếu cần)
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Lọc theo ngày đặt
        if ($request->has('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $bookings = $query->orderBy('id', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách đơn hàng thành công',
            'data' => [
                'bookings' => BookingResource::collection($bookings),
                'pagination' => [
                    'current_page' => $bookings->currentPage(),
                    'last_page' => $bookings->lastPage(),
                    'total' => $bookings->total(),
                ]
            ]
        ]);
    }

    /**
     * Chi tiết booking
     */
    public function show($id)
    {
        $booking = Booking::with([
            'user',
            'tickets.seat',
            'products.product',
            'showtime.movie',
            'showtime.room'
        ])->find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy booking'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new BookingResource($booking)
        ]);
    }

    /**
     * Admin cập nhật trạng thái đơn hàng
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'booking_status' => 'nullable|in:pending,confirmed,canceled,expired,refunded',
            'payment_status' => 'nullable|in:pending,paid,failed,refunded',
        ]);

        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy booking'
            ], 404);
        }

        // Cập nhật
        $booking->update([
            'booking_status' => $request->booking_status ?? $booking->booking_status,
            'payment_status' => $request->payment_status ?? $booking->payment_status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật đơn hàng thành công',
            'data' => new BookingResource($booking)
        ]);
    }

    /**
     * Admin hủy đơn hàng (optional)
     */
    public function cancel($id)
    {
        $booking = Booking::with(['tickets'])->find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy booking'
            ], 404);
        }

        // Cập nhật trạng thái
        $booking->update([
            'booking_status' => 'canceled',
        ]);

        // (Option) mở ghế bị lock nếu đặt vé thất bại
        foreach ($booking->tickets as $ticket) {
            $ticket->seat->update(['status' => 'available']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Hủy đơn hàng thành công',
            'data' => new BookingResource($booking)
        ]);
    }
}
