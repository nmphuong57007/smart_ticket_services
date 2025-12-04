<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingDetailResource extends JsonResource
{
    public function toArray($request)
    {
        // Lấy payment mới nhất (vì booking->payments là quan hệ hasMany)
        $payment = $this->payments->sortByDesc('id')->first();

        return [
            // THÔNG TIN ĐƠN HÀNG
            'id'               => $this->id,
            'booking_code'     => $this->booking_code,
            'payment_status'   => $this->payment_status,
            'transaction_code' => $payment->transaction_code ?? null,
            'payment_method'   => $payment->method ?? null,
            'final_amount'     => $this->final_amount,
            'created_at'       => $this->created_at?->format('Y-m-d H:i'),

            // KHÁCH HÀNG
            'user' => [
                'fullname' => $this->user->fullname ?? null,
                'email'    => $this->user->email ?? null,
                'phone'    => $this->user->phone ?? null,
            ],

            // PHIM
            'movie' => [
                'id'       => $this->showtime->movie->id ?? null,
                'title'    => $this->showtime->movie->title ?? null,
                'duration' => $this->showtime->movie->duration ?? null,
                'poster'   => $this->showtime->movie->poster ?? null,
            ],

            // SUẤT CHIẾU
            'showtime' => [
                'id'   => $this->showtime->id ?? null,
                'time' => $this->showtime->show_time ?? null,
                'type' => $this->showtime->type ?? null,
            ],

            // RẠP
            'cinema' => [
                'id'   => $this->showtime->room->cinema->id ?? null,
                'name' => $this->showtime->room->cinema->name ?? null,
            ],

            // PHÒNG
            'room' => [
                'id'   => $this->showtime->room->id ?? null,
                'name' => $this->showtime->room->name ?? null,
            ],

            // GHẾ
            'seats' => $this->tickets->map(function ($ticket) {
                return [
                    'seat_code' => $ticket->seat->seat_code ?? null,
                    'qr_code'   => $ticket->qr_code ?? null,
                ];
            }),

            // SẢN PHẨM KÈM THEO
            'products' => $this->products->map(function ($item) {
                return [
                    'name'     => $item->product->name ?? null,
                    'quantity' => $item->quantity ?? 0,
                ];
            }),
        ];
    }
}
