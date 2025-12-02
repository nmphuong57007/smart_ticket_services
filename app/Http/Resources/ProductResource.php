<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'type'  => $this->type,
            'price' => (float) $this->price,
            'description' => $this->description,
            'stock' => $this->stock,
            'is_active' => (bool) $this->is_active,
            'image' => $this->image ? url($this->image) : null,

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
