<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
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

            // MOVIE
            'movie' => $this->movie ? [
                'id'           => $this->movie->id,
                'title'        => $this->movie->title,
                'poster'       => $this->formatPoster($this->movie->poster),
                'duration'     => $this->movie->duration,
                'release_date' => $this->movie->release_date,
            ] : null,

            // ROOM
            'room' => $this->room ? [
                'id'   => $this->room->id,
                'name' => $this->room->name,
            ] : null,

            // SHOWTIME INFO
            'show_date'     => $this->show_date,
            'show_time'     => $start->format('H:i'),
            'end_time'      => $end->format('H:i'),
            'format'        => $this->format,
            'language_type' => $this->language_type,
            'price'         => (float) $this->price,

            // DATES
            'created_at' => optional($this->created_at)
                ->timezone('Asia/Ho_Chi_Minh')
                ->format('Y-m-d H:i:s'),

            'updated_at' => optional($this->updated_at)
                ->timezone('Asia/Ho_Chi_Minh')
                ->format('Y-m-d H:i:s'),

            // SEATS (FOR SHOWTIME DETAIL)
            'seats' => $this->whenLoaded('seats', function () {
                return $this->seats->map(function ($seat) {
                    return [
                        'id'        => $seat->id,
                        'seat_code' => $seat->seat_code,
                        'type'      => $seat->type,
                        'status'    => $seat->status,

                        'status_label'  => match ($seat->status) {
                            'available' => 'Còn trống',
                            'selected'  => 'Đang chọn',
                            'booked'    => 'Đã đặt',
                            default     => 'Không xác định',
                        },

                        'is_available' => $seat->status === 'available',

                        'price' => (float) $seat->price,
                    ];
                });
            }),
        ];
    }

    private function formatPoster($poster)
    {
        if (!$poster) return null;

        // Nếu đã là URL đầy đủ -> trả nguyên
        if (str_starts_with($poster, 'http')) {
            return $poster;
        }

        // Nếu là đường dẫn trong storage
        return url(Storage::url($poster));
    }
}
