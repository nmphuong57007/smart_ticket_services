<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MovieStatisticsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        $total = $this['total_movies'] ?? 0;
        $showing = $this['showing_movies'] ?? 0;
        $coming = $this['coming_movies'] ?? 0;
        $stopped = $this['stopped_movies'] ?? 0;

        return [
            'overview' => [
                'total_movies' => $total,
                'showing_movies' => $showing,
                'coming_movies' => $coming,
                'stopped_movies' => $stopped,
            ],
            'percentages' => [
                'showing' => $total > 0 ? round(($showing / $total) * 100, 1) : 0,
                'coming'  => $total > 0 ? round(($coming / $total) * 100, 1) : 0,
                'stopped' => $total > 0 ? round(($stopped / $total) * 100, 1) : 0,
            ],
            'by_genre' => $this['movies_by_genre'] ?? [],
            'recent_movies' => MovieResource::collection($this['recent_movies'] ?? []),
            'all_movies' => MovieResource::collection($this['all_movies'] ?? []),
        ];
    }
}
