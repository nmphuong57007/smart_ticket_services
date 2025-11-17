<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ShowtimeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'movie_id'    => $this->movie_id,
            'room_id'     => $this->room_id,
            'cinema_id'   => $this->cinema_id,

            'movie' => $this->movie ? [
                'id'           => $this->movie->id,
                'title'        => $this->movie->title,
                'poster'       => $this->movie->poster,
                'release_date' => $this->movie->release_date,
            ] : null,

            'room' => $this->room ? [
                'id'        => $this->room->id,
                'name'      => $this->room->name,
                'cinema_id' => $this->room->cinema_id,
            ] : null,

            'cinema' => $this->cinema ? [
                'id'   => $this->cinema->id,
                'name' => $this->cinema->name,
            ] : null,

            'show_date'     => $this->show_date,
            'show_time'     => $this->show_time,
            'format'        => $this->format,
            'language_type' => $this->language_type,
            'price'         => (float) $this->price,

            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
