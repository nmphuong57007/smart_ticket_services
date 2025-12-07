<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ComboPreviewResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this['id'],
            'name'        => $this['name'],
            'price'       => (float) $this['price'],
            'description' => $this['description'] ?? null,
            'image'       => $this['image'] ?? null,
            'stock'       => $this['stock'] ?? null,
        ];
    }
}
