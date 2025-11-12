<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SeatReservationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->id,
            'showtime_id'        => $this->showtime_id,
            'status'             => $this->status ?? 'available',
            'reserved_by_user_id' => $this->status === 'reserved' ? $this->user_id : null,
            'booked_by_user_id'  => $this->status === 'booked' ? $this->user_id : null,
            'reserved_at'        => $this->status === 'reserved' && $this->reserved_at
                ? $this->reserved_at->format('Y-m-d H:i:s')
                : null,
            'booked_at'          => $this->status === 'booked' && $this->booked_at
                ? $this->booked_at->format('Y-m-d H:i:s')
                : null,
            'created_at'         => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'         => $this->updated_at?->format('Y-m-d H:i:s'),

            // Thông tin ghế
            'seat' => $this->whenLoaded('seat', new SeatResource($this->seat)),

            // Thông tin suất chiếu
            'showtime' => $this->whenLoaded('showtime', [
                'id'          => $this->showtime?->id,
                'movie_name'  => $this->showtime?->movie?->title,
                'room_name'   => $this->showtime?->room?->name,
                'cinema_name' => $this->showtime?->room?->cinema?->name,
                'start_time'  => $this->showtime?->start_time?->format('Y-m-d H:i'),
            ]),
        ];
    }
}
