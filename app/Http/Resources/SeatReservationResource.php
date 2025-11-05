<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SeatReservationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'showtime_id'  => $this->showtime_id,
            'seat_id'      => $this->seat_id,
            'seat_code'    => $this->seat?->seat_code,
            'seat_type'    => $this->seat?->type,
            'user_id'      => $this->user_id,
            'status'       => $this->status,
            'reserved_at'  => $this->reserved_at?->format('Y-m-d H:i:s'),
            'booked_at'    => $this->booked_at?->format('Y-m-d H:i:s'),
            'created_at'   => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'   => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
