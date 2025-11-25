<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MovieResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'title'        => $this->title,

            'poster' => $this->poster
                ? (Str::startsWith($this->poster, ['http://', 'https://'])
                    ? $this->poster
                    : asset('storage/' . $this->poster))
                : null,

            'trailer'      => $this->trailer,
            'description'  => $this->description,

            'duration'     => $this->duration,
            'format'       => $this->format,

            // NGÔN NGỮ LƯU TRỰC TIẾP → TRẢ RA TRỰC TIẾP
            'language'     => $this->language,

            'release_date' => $this->release_date,
            'end_date'     => $this->end_date,
            'status'       => $this->status,

            'created_at' => $this->created_at
                ? Carbon::parse($this->created_at)->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d H:i:s')
                : null,

            'updated_at' => $this->updated_at
                ? Carbon::parse($this->updated_at)->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d H:i:s')
                : null,

            // GENRES
            'genres' => $this->whenLoaded('genres', function () {
                return $this->genres->map(function ($genre) {
                    return [
                        'id'   => $genre->id,
                        'name' => $genre->name,
                    ];
                });
            }),
        ];
    }
}
