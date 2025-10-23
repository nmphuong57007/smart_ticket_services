<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SeatResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'seat_code' => $this->seat_code,
            'type' => $this->type,
            'price' => (float) $this->price,
            'status' => $this->status,
        ];
    }
}
