<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SeatPreviewResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'        => $this['id'],
            'seat_code' => $this['seat_code'],
            'type'      => $this['type'],
            'status'    => $this['status'],
            'price'     => (float) $this['price'],
        ];
    }
}
