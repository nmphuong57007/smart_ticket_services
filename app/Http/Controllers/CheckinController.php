<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckinController extends Controller
{
    public function checkIn(Request $request)
    {
        $request->validate([
            'qr_code' => 'required|string',
        ]);

        $qrString = $request->input('qr_code');

        // base64 -> json
        $json = base64_decode($qrString, true);
        if ($json === false) {
            return response()->json([
                'success' => false,
                'message' => 'QR không hợp lệ (không giải mã được base64)',
            ], 400);
        }

        $data = json_decode($json, true);

        if (!is_array($data) || !isset($data['ticket_id'], $data['booking_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'QR không hợp lệ (thiếu ticket_id/booking_id)',
            ], 400);
        }

        // Tìm ticket + load booking detail để trả ghế & product
        $ticket = Ticket::with([
                'booking.user',
                'booking.bookingSeats.seat',
                'booking.products.product',
                'booking.showtime.movie',
                'booking.showtime.room.cinema',
            ])
            ->where('id', $data['ticket_id'])
            ->where('booking_id', $data['booking_id'])
            ->where('qr_code', $qrString)
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Vé không tồn tại hoặc QR đã bị sửa!',
            ], 404);
        }

        // Check-in rồi
        if ($ticket->is_checked_in) {
            return response()->json([
                'success' => false,
                'message' => 'Vé này đã được check-in trước đó!',
                'data' => [
                    'ticket_id'     => $ticket->id,
                    'booking_id'    => $ticket->booking_id,
                    'checked_in_at' => $ticket->checked_in_at,
                ],
            ], 409);
        }

        // Đánh dấu check-in (1 lần cho toàn bộ vé/booking)
        $ticket->is_checked_in = true;
        $ticket->checked_in_at = now();
        if (Auth::check()) {
            $ticket->checked_in_by = Auth::id();
        }
        $ticket->save();

        // Build response: danh sách ghế + products
        $booking = $ticket->booking;

        $seats = $booking->bookingSeats->map(function ($bs) {
            return [
                'seat_id'   => $bs->seat_id,
                'seat_code' => optional($bs->seat)->seat_code ?? optional($bs->seat)->code ?? null,
                'row'       => optional($bs->seat)->row ?? null,
                'col'       => optional($bs->seat)->col ?? null,
                'type'      => optional($bs->seat)->type ?? null,
            ];
        })->values();

        $products = $booking->products->map(function ($bp) {
            return [
                'product_id' => $bp->product_id,
                'name'       => optional($bp->product)->name ?? null,
                'quantity'   => $bp->quantity,
                'price'      => optional($bp->product)->price ?? null,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Check-in thành công!',
            'data' => [
                'ticket' => [
                    'ticket_id'      => $ticket->id,
                    'qr_code'        => $ticket->qr_code,
                    'is_checked_in'  => $ticket->is_checked_in,
                    'checked_in_at'  => $ticket->checked_in_at,
                    'checked_in_by'  => $ticket->checked_in_by,
                ],
                'booking' => [
                    'booking_id'     => $booking->id,
                    'booking_code'   => $booking->booking_code,
                    'payment_status' => $booking->payment_status,
                    'booking_status' => $booking->booking_status,
                    'final_amount'   => $booking->final_amount,
                    'created_at'     => $booking->created_at,
                ],
                'showtime' => [
                    'showtime_id' => optional($booking->showtime)->id,
                    'date'        => optional($booking->showtime)->date ?? null,
                    'time'        => optional($booking->showtime)->time ?? null,
                    'movie'       => [
                        'id'    => optional(optional($booking->showtime)->movie)->id,
                        'title' => optional(optional($booking->showtime)->movie)->title,
                    ],
                    'cinema' => [
                        'id'   => optional(optional(optional($booking->showtime)->room)->cinema)->id,
                        'name' => optional(optional(optional($booking->showtime)->room)->cinema)->name,
                    ],
                    'room' => [
                        'id'   => optional(optional($booking->showtime)->room)->id,
                        'name' => optional(optional($booking->showtime)->room)->name,
                    ],
                ],
                'seats'    => $seats,
                'products' => $products,
            ],
        ]);
    }
}
