<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class BookingDetailResource extends JsonResource
{
    public function toArray($request)
    {
        // Payment mới nhất
        $payment = $this->payments?->sortByDesc('id')->first();

        // QR theo booking
        $qrCode = $this->ticket?->qr_code;

        // Poster phim
        $poster = $this->showtime?->movie?->poster;
        $posterUrl = $poster
            ? (str_starts_with($poster, 'http') ? $poster : url('storage/' . $poster))
            : null;

        return [
            // ======= THÔNG TIN ĐƠN HÀNG =======
            'id'               => $this->id,
            'booking_code'     => $this->booking_code,
            'payment_status'   => $this->payment_status,
            'booking_status'   => $this->booking_status,
            'transaction_code' => $payment->transaction_code ?? null,
            'payment_method'   => $payment->method ?? null,
            'final_amount'     => $this->final_amount,

            // GIỜ VIỆT NAM
            'created_at' => $this->created_at
                ? $this->created_at->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d H:i:s')
                : null,

            // ======= CHECK-IN =======
            'qr_code'       => $qrCode,
            'is_checked_in' => (bool) $this->ticket?->is_checked_in,

            'checked_in_at' => $this->ticket?->checked_in_at
                ? $this->ticket->checked_in_at->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d H:i:s')
                : null,

            'checked_in_by' => $this->ticket?->checked_in_by,

            // ======= NGƯỜI DÙNG =======
            'user' => [
                'fullname' => $this->user?->fullname,
                'email'    => $this->user?->email,
                'phone'    => $this->user?->phone,
            ],

            // ======= PHIM =======
            'movie' => [
                'id'       => $this->showtime?->movie?->id,
                'title'    => $this->showtime?->movie?->title,
                'duration' => $this->showtime?->movie?->duration,
                'poster'   => $posterUrl,
            ],

            // ======= SUẤT CHIẾU =======
            'showtime' => [
                'id'   => $this->showtime?->id,
                'date' => $this->showtime?->show_date,
                'time' => $this->showtime?->show_time,
                'type' => $this->showtime?->type,
            ],

            // ======= RẠP & PHÒNG =======
            'cinema' => [
                'id'   => $this->showtime?->room?->cinema?->id,
                'name' => $this->showtime?->room?->cinema?->name,
            ],

            'room' => [
                'id'   => $this->showtime?->room?->id,
                'name' => $this->showtime?->room?->name,
            ],

            // ======= GHẾ =======
            'seats' => $this->bookingSeats
                ? $this->bookingSeats->map(function ($item) {
                    $seat = $item->seat;

                    return [
                        'id'        => $seat?->id,
                        'seat_code' => $seat?->seat_code,
                        'type'      => $seat?->type,
                        // LƯU GIÁ GHẾ TẠI THỜI ĐIỂM BOOKING
                        'price' => (int) $item->price,
                    ];
                })->values()
                : [],

            // ======= SẢN PHẨM =======
            'products' => $this->products
                ? $this->products->map(function ($item) {
                    return [
                        'name'     => $item->product->name ?? null,
                        'quantity' => $item->quantity ?? 0,
                        'price'    => $item->product->price ?? 0,
                    ];
                })->values()
                : [],
        ];
    }
}
