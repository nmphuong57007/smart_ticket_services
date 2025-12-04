<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingListResource extends JsonResource
{
    public function toArray($request)
    {
        // Lấy payment mới nhất (vì booking->payments là hasMany)
        $payment = $this->payments->sortByDesc('id')->first();

        return [
            'id'               => $this->id,
            'booking_code'     => $this->booking_code,

            // KHÁCH HÀNG
            'email'            => $this->user->email ?? null,

            // PHIM
            'movie_title'      => $this->showtime->movie->title ?? null,

            // RẠP
            'cinema'           => $this->showtime->room->cinema->name ?? null,

            // NGÀY ĐẶT
            'booking_date'     => $this->created_at?->format('Y-m-d H:i') ?? null,

            // THANH TOÁN
            'payment_method'   => $payment->method ?? null,
            'transaction_code' => $payment->transaction_code ?? null,

            // TỔNG TIỀN
            'total_amount'     => $this->final_amount,
        ];
    }
}
