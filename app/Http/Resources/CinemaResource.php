<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CinemaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'      => $this->id,
            'name'    => $this->name,
            'address' => $this->address,
            'phone'   => $this->phone,
            'status'  => $this->status,

            // Luôn load rooms nếu controller gọi with('rooms')
            'rooms' => $this->whenLoaded('rooms', function () {
                return $this->rooms->map(function ($room) {
                    return [
                        'id'          => $room->id,
                        'name'        => $room->name,
                        'status'      => $room->status,
                        'total_seats' => $room->total_seats,
                    ];
                });
            }),
        ];
    }
}
