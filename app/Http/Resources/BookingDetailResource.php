<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingDetailResource extends JsonResource
{
    public function toArray($request)
    {
        // Lấy payment mới nhất
        $payment = $this->payments->sortByDesc('id')->first();

        // Nếu booking đã thanh toán -> dùng tickets thay vì booking_seats
        $isPaid = $this->payment_status === 'paid';

        // Xử lý poster phim
        $poster = $this->showtime->movie->poster ?? null;

        if ($poster) {
            if (str_starts_with($poster, 'http')) {
                // Nếu đã là URL tuyệt đối
                $posterUrl = $poster;
            } else {
                // Nếu là file trong storage
                $posterUrl = url('storage/' . $poster);
            }
        } else {
            $posterUrl = null;
        }

        return [

            // ======= THÔNG TIN ĐƠN HÀNG =======
            'id'               => $this->id,
            'booking_code'     => $this->booking_code,
            'payment_status'   => $this->payment_status,
            'booking_status'   => $this->booking_status,
            'transaction_code' => $payment->transaction_code ?? null,
            'payment_method'   => $payment->method ?? null,
            'final_amount'     => $this->final_amount,
            'created_at'       => $this->created_at?->format('Y-m-d H:i'),

            // ======= NGƯỜI DÙNG =======
            'user' => [
                'fullname' => $this->user->fullname ?? null,
                'email'    => $this->user->email ?? null,
                'phone'    => $this->user->phone ?? null,
            ],

            // ======= THÔNG TIN PHIM =======
            'movie' => [
                'id'       => $this->showtime->movie->id ?? null,
                'title'    => $this->showtime->movie->title ?? null,
                'duration' => $this->showtime->movie->duration ?? null,
                'poster'   => $posterUrl, // FIXED
            ],

            // ======= SUẤT CHIẾU =======
            'showtime' => [
                'id'   => $this->showtime->id ?? null,
                'date' => $this->showtime->show_date ?? null,
                'time' => $this->showtime->show_time ?? null,
                'type' => $this->showtime->type ?? null,
            ],

            // ======= RẠP & PHÒNG =======
            'cinema' => [
                'id'   => $this->showtime->room->cinema->id ?? null,
                'name' => $this->showtime->room->cinema->name ?? null,
            ],

            'room' => [
                'id'   => $this->showtime->room->id ?? null,
                'name' => $this->showtime->room->name ?? null,
            ],

            // ======= GHẾ =======
            'seats' => $isPaid
                ? $this->tickets->map(function ($ticket) {
                    return [
                        'id'         => $ticket->seat->id,
                        'seat_code'  => $ticket->seat->seat_code,
                        'type'       => $ticket->seat->type,
                        'price'      => $ticket->seat->price,
                        'qr_code'    => $ticket->qr_code,
                    ];
                })
                : $this->bookingSeats->map(function ($item) {
                    return [
                        'id'         => $item->seat->id,
                        'seat_code'  => $item->seat->seat_code,
                        'type'       => $item->seat->type,
                        'price'      => $item->seat->price,
                        'qr_code'    => null,
                    ];
                }),

            // ======= SẢN PHẨM =======
            'products' => $this->products->map(function ($item) {
                return [
                    'name'     => $item->product->name ?? null,
                    'quantity' => $item->quantity ?? 0,
                    'price'    => $item->product->price ?? 0,
                ];
            }),
        ];
    }
}
