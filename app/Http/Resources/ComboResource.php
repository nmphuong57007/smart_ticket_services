<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ComboResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => (float) $this->price,
            'description' => $this->description,
            'image' => $this->image ? url($this->image) : null,
            'stock' => (int) $this->stock,
            'type' => $this->type,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
