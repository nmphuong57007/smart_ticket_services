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
                'current_page' => $this->currentPage(),
                'last_page'    => $this->lastPage(),
                'per_page'     => $this->perPage(),
                'total'        => $this->total(),
            ],
        ];
    }
}
