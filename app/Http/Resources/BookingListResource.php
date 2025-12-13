<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingListResource extends JsonResource
{
    public function toArray($request)
    {
        // Payment mới nhất (tránh lỗi nếu payments chưa load)
        $payment = $this->payments?->sortByDesc('id')->first();

        // Lấy danh sách seat codes (LUÔN lấy từ booking_seats)
        $seats = $this->bookingSeats
            ? $this->bookingSeats
            ->map(fn($bs) => $bs->seat?->seat_code)
            ->filter()
            ->values()
            : collect();

        // Xử lý poster phim
        $poster = $this->showtime?->movie?->poster;

        if ($poster) {
            $posterUrl = str_starts_with($poster, 'http')
                ? $poster
                : url('storage/' . $poster);
        } else {
            $posterUrl = null;
        }

        return [
            // ===== THÔNG TIN CƠ BẢN =====
            'id'            => $this->id,
            'booking_code'  => $this->booking_code,
            'booking_date'  => $this->created_at?->format('Y-m-d H:i'),

            // ===== TRẠNG THÁI =====
            'booking_status' => $this->booking_status,
            'payment_status' => $this->payment_status,

            // ===== KHÁCH HÀNG =====
            'email' => $this->user?->email,

            // ===== PHIM =====
            'movie_title'  => $this->showtime?->movie?->title,
            'movie_poster' => $posterUrl,

            // ===== RẠP & PHÒNG =====
            'cinema'    => $this->showtime?->room?->cinema?->name,
            'room_name' => $this->showtime?->room?->name,

            // ===== GHẾ =====
            'seats'      => $seats,
            'seat_count' => $seats->count(),

            // ===== THANH TOÁN =====
            'payment_method'   => $payment->method ?? null,
            'transaction_code' => $payment->transaction_code ?? null,

            // ===== SỐ TIỀN =====
            'final_amount' => $this->final_amount,

            // ===== QR (1 booking = 1 QR) =====
            'qr_code' => $this->ticket?->qr_code,
        ];
    }
}
