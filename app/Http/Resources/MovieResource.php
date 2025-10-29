<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

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
            'poster'       => $this->poster ? url($this->poster) : null,
            'trailer'      => $this->trailer,
            'description'  => $this->description,
            'genre'        => $this->genre,
            'duration'     => $this->duration,
            'format'       => $this->format,
            'language'     => $this->language,
            'release_date' => $this->release_date,
            'end_date'     => $this->end_date,
            'status'       => $this->status,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
