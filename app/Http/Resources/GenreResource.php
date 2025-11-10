<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GenreResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'is_active'  => $this->is_active,
            'created_at' => $this->created_at
                ? $this->created_at->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d H:i:s')
                : null,
            'updated_at' => $this->updated_at
                ? $this->updated_at->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d H:i:s')
                : null,
        ];
    }
}
