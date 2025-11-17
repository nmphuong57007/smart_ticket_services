<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class ShowtimeResource extends JsonResource
{
    public function toArray($request)
    {
        // Tính giờ kết thúc (không tính buffer)
        $endTime = Carbon::parse("{$this->show_date} {$this->show_time}")
            ->addMinutes($this->movie->duration ?? 0)
            ->format('H:i');

        return [
            'id'        => $this->id,
            'movie_id'  => $this->movie_id,
            'room_id'   => $this->room_id,
            'cinema_id' => $this->cinema_id,

            // Movie info
            'movie' => $this->movie ? [
                'id'           => $this->movie->id,
                'title'        => $this->movie->title,
                'poster'       => $this->movie->poster,
                'duration'     => $this->movie->duration,
                'release_date' => $this->movie->release_date,
            ] : null,

            // Room info
            'room' => $this->room ? [
                'id'        => $this->room->id,
                'name'      => $this->room->name,
                'cinema_id' => $this->room->cinema_id,
            ] : null,

            // Cinema info
            'cinema' => $this->cinema ? [
                'id'   => $this->cinema->id,
                'name' => $this->cinema->name,
            ] : null,

            // Showtime info
            'show_date' => $this->show_date,
            'show_time' => Carbon::parse($this->show_time)->format('H:i'),
            'end_time'  => $endTime, // Giờ kết thúc của phim
            'format'    => $this->format,
            'language_type' => $this->language_type,
            'price'     => (float) $this->price,

            // Timestamps
            'created_at' => $this->created_at
                ? $this->created_at->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d H:i:s')
                : null,

            'updated_at' => $this->updated_at
                ? $this->updated_at->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d H:i:s')
                : null,
        ];
    }
}
