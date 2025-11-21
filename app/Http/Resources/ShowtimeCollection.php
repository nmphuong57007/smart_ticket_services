<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ShowtimeCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'items' => ShowtimeResource::collection($this->collection),

            'pagination' => [
                'current_page' => $this->resource->currentPage(),
                'last_page'    => $this->resource->lastPage(),
                'per_page'     => $this->resource->perPage(),
                'total'        => $this->resource->total(),
            ],
        ];
    }
}
