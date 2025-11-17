<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class CinemaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'address'    => $this->address,
            'phone'      => $this->phone,
            'status'     => $this->status,
            'rooms_count' => $this->when(isset($this->rooms_count), $this->rooms_count),

            // Khi load quan hệ rooms
            'rooms' => $this->whenLoaded('rooms', function () {
                return $this->rooms->map(fn($room) => [
                    'id'     => $room->id,
                    'name'   => $room->name,
                    'status' => $room->status,
                ]);
            }),

            'created_at' => optional($this->created_at)
                ->timezone('Asia/Ho_Chi_Minh')
                ->format('Y-m-d H:i:s'),

            'updated_at' => optional($this->updated_at)
                ->timezone('Asia/Ho_Chi_Minh')
                ->format('Y-m-d H:i:s'),
        ];
    }

    private function formatDate($date): ?string
    {
        return $date
            ? Carbon::parse($date)->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d H:i:s')
            : null;
    }
}
