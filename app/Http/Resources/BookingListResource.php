<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingListResource extends JsonResource
{
    public function toArray($request)
    {
        $payment = $this->payments?->sortByDesc('id')->first();

        $seats = $this->bookingSeats
            ? $this->bookingSeats
                ->map(fn ($bs) => $bs->seat?->seat_code)
                ->filter()
                ->values()
            : collect();

        $poster = $this->showtime?->movie?->poster;
        $posterUrl = $poster
            ? (str_starts_with($poster, 'http') ? $poster : url('storage/' . $poster))
            : null;

        return [
            // ===== THÔNG TIN CƠ BẢN =====
            'id'           => $this->id,
            'booking_code' => $this->booking_code,

            // GIỜ VIỆT NAM
            'booking_date' => $this->created_at
                ? $this->created_at->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d H:i:s')
                : null,

            // ===== TRẠNG THÁI =====
            'booking_status' => $this->booking_status,
            'payment_status' => $this->payment_status,

            // CHECK-IN (QUAN TRỌNG)
            'is_checked_in' => (bool) $this->ticket?->is_checked_in,
            'checked_in_at' => $this->ticket?->checked_in_at
                ? $this->ticket->checked_in_at->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d H:i:s')
                : null,

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

            // ===== TIỀN =====
            'final_amount' => $this->final_amount,

            // ===== QR =====
            'qr_code' => $this->ticket?->qr_code,
        ];
    }
}
