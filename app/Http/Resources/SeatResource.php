<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SeatResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'        => $this->id,
            'room_id'   => $this->room_id,
            'cinema_id' => $this->cinema_id,
            'room_name' => $this->room?->name ?? null,
            'seat_code' => $this->seat_code,
            'type'      => $this->type,
            'status'    => $this->status ?? 'available',
            'price'     => (float) $this->price,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
