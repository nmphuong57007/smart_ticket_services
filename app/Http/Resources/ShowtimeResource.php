<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class ShowtimeResource extends JsonResource
{
    public function toArray($request)
    {
        $start = Carbon::parse("{$this->show_date} {$this->show_time}");
        $duration = $this->movie->duration ?? 0;

        
        $end = $start->copy()->addMinutes($duration);

        return [
            'id'        => $this->id,
            'movie_id'  => $this->movie_id,
            'room_id'   => $this->room_id,


            'movie' => [
                'id'           => $this->movie->id ?? null,
                'title'        => $this->movie->title ?? null,
                'poster'       => $this->movie->poster ?? null,
                'duration'     => $this->movie->duration ?? null,
                'release_date' => $this->movie->release_date ?? null,
            ],


            'room' => [
                'id'   => $this->room->id ?? null,
                'name' => $this->room->name ?? null,
            ],


            'show_date' => $this->show_date,
            'show_time' => $start->format('H:i'),
            'end_time'  => $end->format('H:i'),

            'format'        => $this->format,
            'language_type' => $this->language_type,
            'price'         => (float) $this->price,

            'created_at' => optional($this->created_at)->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d H:i:s'),
            'updated_at' => optional($this->updated_at)->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d H:i:s'),

            // THÊM PHẦN QUAN TRỌNG NÀY
            'seats' => $this->whenLoaded('seats', function () {
                return $this->seats->map(function ($seat) {
                    return [
                        'id'            => $seat->id,
                        'seat_code'     => $seat->seat_code,
                        'type'          => $seat->type,
                        'status'        => $seat->status,
                        'status_label'  => match ($seat->status) {
                            'available' => 'Còn trống',
                            'selected'  => 'Đang chọn',
                            'booked'    => 'Đã đặt',
                            default     => 'Không xác định',
                        },
                        'price'         => (float) $seat->price,
                    ];
                });
            }),
        ];
    }
}
