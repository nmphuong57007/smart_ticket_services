<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Http\Resources\CheckinResource;
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

        // Tìm ticket + load booking detail
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

        // Đã check-in
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

        // Check-in
        $ticket->is_checked_in = true;
        $ticket->checked_in_at = now();
        if (Auth::check()) {
            $ticket->checked_in_by = Auth::id();
        }
        $ticket->save();

        return response()->json([
            'success' => true,
            'message' => 'Check-in thành công!',
            'data' => new CheckinResource($ticket),
        ]);
    }
}
