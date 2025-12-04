<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray($request): array
    {
        $showtime = $this->booking->showtime;
        $movie    = $showtime->movie;
        $room     = $showtime->room;
        $cinema   = $room->cinema;

        return [
            'id'      => $this->id,
            'qr_code' => $this->qr_code,

            // GHẾ
            'seat' => [
                'id'        => $this->seat->id,
                'seat_code' => $this->seat->seat_code,
                'type'      => $this->seat->type,
                'price'     => (float) $this->seat->price,
            ],

            // SUẤT CHIẾU
            'showtime' => [
                'id'        => $showtime->id,
                'date'      => $showtime->show_date,
                'time'      => $showtime->show_time,
                'format'    => $showtime->format,
                'language'  => $showtime->language_type,
            ],

            // PHIM
            'movie' => [
                'id'        => $movie->id,
                'title'     => $movie->title,
                'poster'    => $movie->poster,
                'duration'  => $movie->duration,
            ],

            // PHÒNG & RẠP
            'room' => [
                'id'    => $room->id,
                'name'  => $room->name,
            ],

            'cinema' => [
                'id'    => $cinema->id,
                'name'  => $cinema->name,
            ],

            // THỜI GIAN TẠO
            'created_at' => $this->created_at
                ? $this->created_at->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d H:i:s')
                : null,

        ];
    }
}
