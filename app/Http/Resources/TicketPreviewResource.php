<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TicketPreviewResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            //  MOVIE
            'movie' => [
                'id'     => $this['showtime']['movie']['id'] ?? null,
                'title'  => $this['showtime']['movie']['title'] ?? null,
            ],

            //  SHOWTIME
            'showtime' => [
                'id'        => $this['showtime']['id'],
                'date'      => $this['showtime']['show_date'],
                'time'      => $this['showtime']['show_time'],
            ],

            //  ROOM
            'room' => [
                'id'   => $this['showtime']['room']['id'],
                'name' => $this['showtime']['room']['name'],
            ],

            // //  CINEMA
            // 'cinema' => [
            //     'id'   => $this['showtime']['room']['cinema']['id'],
            //     'name' => $this['showtime']['room']['cinema']['name'],
            // ],

            //  SEATS
            'seats' => SeatPreviewResource::collection($this['seats']),

            //  COMBOS
            'combos' => ComboPreviewResource::collection($this['combos']),

            //  PRICE GROUP
            'pricing' => [
                'seat_total'   => $this['seats']->sum(fn($s) => $s['price']),
                'combo_total'  => $this['combos']->sum(fn($c) => $c['price']),
                'total'        => $this['total_price'],
                'discount'     => $this['discount'],
                'final_amount' => $this['final_amount'],
            ],

            //  PROMOTION
            'promotion' => $this['promotion'] ? [
                'code'                => $this['promotion']->code,
                'type'                => $this['promotion']->type,
                'discount_percent'    => $this['promotion']->discount_percent,
                'discount_amount'     => $this['promotion']->discount_amount,
                'max_discount_amount' => $this['promotion']->max_discount_amount,
                'min_order_amount'    => $this['promotion']->min_order_amount,
            ] : null,
        ];
    }
}
