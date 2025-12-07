<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingListResource extends JsonResource
{
    public function toArray($request)
    {
        // Payment mới nhất
        $payment = $this->payments->sortByDesc('id')->first();

        // Xác định dùng seats từ bảng nào
        $isPaid = $this->payment_status === 'paid';

        // Lấy danh sách seat codes
        $seats = $isPaid
            ? $this->tickets->map(fn($t) => $t->seat->seat_code)
            : $this->bookingSeats->map(fn($bs) => $bs->seat->seat_code);

        // Xử lý poster phim
        $poster = $this->showtime->movie->poster ?? null;

        if ($poster) {
            // Nếu là URL tuyệt đối → giữ nguyên
            if (str_starts_with($poster, 'http')) {
                $posterUrl = $poster;
            } else {
                // Nếu là file trong storage → thêm domain
                $posterUrl = url('storage/' . $poster);
            }
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
            'email' => $this->user->email ?? null,

            // ===== PHIM =====
            'movie_title'  => $this->showtime->movie->title ?? null,
            'movie_poster' => $posterUrl,

            // ===== RẠP & PHÒNG =====
            'cinema'    => $this->showtime->room->cinema->name ?? null,
            'room_name' => $this->showtime->room->name ?? null,

            // ===== GHẾ =====
            'seats'      => $seats,
            'seat_count' => $seats->count(),

            // ===== THANH TOÁN =====
            'payment_method'   => $payment->method ?? null,
            'transaction_code' => $payment->transaction_code ?? null,

            // ===== SỐ TIỀN =====
            'final_amount' => $this->final_amount,
        ];
    }
}
