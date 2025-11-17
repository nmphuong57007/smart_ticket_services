<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SeatResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,

            // GHẾ
            'seat_code'  => $this->seat_code,
            'type'       => $this->type,
            'status'     => $this->status,
            'price'      => (float) $this->price,

            // ROOM
            'room' => $this->whenLoaded('room', function () {
                return [
                    'id'   => $this->room->id,
                    'name' => $this->room->name,

                    // CINEMA (qua room)
                    'cinema' => $this->whenLoaded('room', function () {
                        return [
                            'id'   => $this->room->cinema->id ?? null,
                            'name' => $this->room->cinema->name ?? null,
                        ];
                    }),
                ];
            }),

            'created_at' => optional($this->created_at)
                ->timezone('Asia/Ho_Chi_Minh')
                ->format('Y-m-d H:i:s'),

            'updated_at' => optional($this->updated_at)
                ->timezone('Asia/Ho_Chi_Minh')
                ->format('Y-m-d H:i:s'),
        ];
    }
}
