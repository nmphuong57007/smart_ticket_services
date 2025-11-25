<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ContentPostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        return [
            'id'                => $this->id,
            'type'              => $this->type, // banner | news | promotion
            'title'             => $this->title,
            'short_description' => $this->short_description,
            'description'       => $this->description,
            'slug'              => $this->slug,

            // Nếu có ảnh thì trả full URL
            'image' => $this->image
                ? url(Storage::url($this->image))
                : null,

            'is_published'      => $this->is_published,
            'published_at' => optional($this->published_at)
                ->timezone('Asia/Ho_Chi_Minh')
                ->format('Y-m-d H:i:s'),

            'created_by'        => $this->created_by,
            'created_by_name'   => $this->created_by_name,

            'created_at' => optional($this->created_at)
                ->timezone('Asia/Ho_Chi_Minh')
                ->format('Y-m-d H:i:s'),

            'updated_at' => optional($this->updated_at)
                ->timezone('Asia/Ho_Chi_Minh')
                ->format('Y-m-d H:i:s'),

            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id'       => $this->creator->id,
                    'fullname' => $this->creator->fullname,
                ];
            }),
        ];
    }
}
