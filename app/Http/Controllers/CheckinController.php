<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckinController extends Controller
{
    public function checkIn(Request $request)
    {
        // 1. Validate input
        $request->validate([
            'qr_code' => 'required|string',
        ]);

        $qrString = $request->input('qr_code');

        // 2. Giải mã chuỗi QR: base64 -> JSON -> array
        $json = base64_decode($qrString, true);

        if ($json === false) {
            return response()->json([
                'success' => false,
                'message' => 'QR không hợp lệ (không giải mã được base64)',
            ], 400);
        }

        $data = json_decode($json, true);

        if (!is_array($data) || !isset($data['ticket_id'], $data['booking_id'], $data['seat_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'QR không hợp lệ (thiếu dữ liệu)',
            ], 400);
        }

        // 3. Tìm vé trong DB & đảm bảo QR đúng với vé
        $ticket = Ticket::where('id', $data['ticket_id'])
            ->where('booking_id', $data['booking_id'])
            ->where('seat_id', $data['seat_id'])
            ->where('qr_code', $qrString) // chống sửa payload
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Vé không tồn tại hoặc QR đã bị sửa!',
            ], 404);
        }

        // 4. Kiểm tra đã check-in chưa
        if ($ticket->is_checked_in) {
            return response()->json([
                'success' => false,
                'message' => 'Vé này đã được check-in trước đó!',
                'data' => [
                    'ticket_id'     => $ticket->id,
                    'checked_in_at' => $ticket->checked_in_at,
                ],
            ], 409);
        }

        // (Tuỳ bạn) 5. Có thể kiểm tra thêm giờ chiếu đã quá hạn chưa:
        // if ($ticket->booking->showtime->start_time < now()->subMinutes(15)) { ... }

        // 6. Đánh dấu check-in
        $ticket->is_checked_in = true;
        $ticket->checked_in_at = now();

        // Nếu bạn dùng Auth cho nhân viên:
        if (Auth::check()) {
            $ticket->checked_in_by = Auth::id();
        }

        $ticket->save();

        return response()->json([
            'success' => true,
            'message' => 'Check-in thành công!',
            'data' => [
                'ticket_id'   => $ticket->id,
                'booking_id'  => $ticket->booking_id,
                'seat_id'     => $ticket->seat_id,
                'is_checked_in' => $ticket->is_checked_in,
                'checked_in_at' => $ticket->checked_in_at,
            ],
        ]);
    }
}
