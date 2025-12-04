<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingListResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'               => $this->id,
            'booking_code'     => $this->booking_code,                      // Mã ĐH
            'email'            => $this->user->email,                       // Email KH
            'movie_title'      => $this->showtime->movie->title,            // Tên phim
            'cinema'           => $this->showtime->room->cinema->name,      // Rạp
            'booking_date'     => $this->created_at->format('Y-m-d H:i'),   // Ngày đặt
            'payment_method'   => $this->payment->method ?? null,           // Cổng thanh toán
            'transaction_code' => $this->payment->transaction_code ?? null, // Mã giao dịch
            'total_amount'     => $this->final_amount,                      // Tổng tiền đơn
        ];
    }
}
