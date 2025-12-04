<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            //  THÔNG TIN ĐƠN HÀNG 
            'booking_code'     => $this->booking_code,
            'payment_status'   => $this->payment_status,
            'transaction_code' => $this->payment->transaction_code ?? null,
            'payment_method'   => $this->payment->method ?? null,
            'final_amount'     => $this->final_amount,
            'created_at'       => $this->created_at?->format('Y-m-d H:i'),

            //  THÔNG TIN KHÁCH HÀNG ----------
            'user' => [
                'fullname' => $this->user->fullname ?? null,
                'email'    => $this->user->email ?? null,
                'phone'    => $this->user->phone ?? null,
            ],

            //  THÔNG TIN PHIM 
            'movie' => [
                'id'       => $this->showtime->movie->id ?? null,
                'title'    => $this->showtime->movie->title ?? null,
                'duration' => $this->showtime->movie->duration ?? null,
                'poster'   => $this->showtime->movie->poster ?? null,
            ],

            //  THÔNG TIN SUẤT CHIẾU 
            'showtime' => [
                'id'       => $this->showtime->id ?? null,
                'time'     => $this->showtime->show_time ?? null,
                'type'     => $this->showtime->type ?? null,
            ],

            //  RẠP & PHÒNG 
            'cinema' => [
                'id'   => $this->showtime->room->cinema->id ?? null,
                'name' => $this->showtime->room->cinema->name ?? null,
            ],

            'room' => [
                'id'   => $this->showtime->room->id ?? null,
                'name' => $this->showtime->room->name ?? null,
            ],

            //  GHẾ 
            'seats' => $this->tickets->map(function ($ticket) {
                return [
                    'seat_code' => $ticket->seat->seat_code ?? null,
                    'qr_code'   => $ticket->qr_code ?? null,
                ];
            }),

            // --------- SẢN PHẨM MUA KÈM 
            'products' => $this->bookingProducts->map(function ($item) {
                return [
                    'name'     => $item->product->name ?? null,
                    'quantity' => $item->quantity ?? 0,
                ];
            }),
        ];
    }
}
