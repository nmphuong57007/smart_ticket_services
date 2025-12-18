<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CheckinResource extends JsonResource
{
    public function toArray($request)
    {
        $ticket  = $this->resource;
        $booking = $ticket->booking;

        $seats = $booking->bookingSeats->map(function ($bs) {
            return [
                'seat_id'   => $bs->seat_id,
                'seat_code' => optional($bs->seat)->seat_code ?? optional($bs->seat)->code ?? null,
                'type'      => optional($bs->seat)->type ?? null,
                'price' => (int) $bs->price,
            ];
        })->values();

        $products = $booking->products->map(function ($bp) {
            return [
                'product_id' => $bp->product_id,
                'name'       => optional($bp->product)->name ?? null,
                'quantity'   => $bp->quantity,
                'price'      => optional($bp->product)->price ?? null,
            ];
        })->values();

        return [
            'ticket' => [
                'ticket_id'     => $ticket->id,
                'qr_code'       => $ticket->qr_code,
                'is_checked_in' => (bool) $ticket->is_checked_in,

                // GIỜ VIỆT NAM
                'checked_in_at' => $ticket->checked_in_at
                    ? $ticket->checked_in_at
                    ->timezone('Asia/Ho_Chi_Minh')
                    ->format('Y-m-d H:i:s')
                    : null,

                'checked_in_by' => $ticket->checked_in_by,
            ],

            'booking' => [
                'booking_id'     => $booking->id,
                'booking_code'   => $booking->booking_code,
                'payment_status' => $booking->payment_status,
                'booking_status' => $booking->booking_status,
                'final_amount'   => $booking->final_amount,

                // GIỜ VIỆT NAM
                'created_at' => $booking->created_at
                    ? $booking->created_at
                    ->timezone('Asia/Ho_Chi_Minh')
                    ->format('Y-m-d H:i:s')
                    : null,
            ],

            'showtime' => [
                'showtime_id' => optional($booking->showtime)->id,
                'date'        => optional($booking->showtime)->show_date ?? null,
                'time'        => optional($booking->showtime)->show_time ?? null,

                'movie' => [
                    'id'    => optional(optional($booking->showtime)->movie)->id,
                    'title' => optional(optional($booking->showtime)->movie)->title,
                ],

                'cinema' => [
                    'id'   => optional(optional(optional($booking->showtime)->room)->cinema)->id,
                    'name' => optional(optional(optional($booking->showtime)->room)->cinema)->name,
                ],

                'room' => [
                    'id'   => optional(optional($booking->showtime)->room)->id,
                    'name' => optional(optional($booking->showtime)->room)->name,
                ],
            ],

            'seats'    => $seats,
            'products' => $products,
        ];
    }
}
