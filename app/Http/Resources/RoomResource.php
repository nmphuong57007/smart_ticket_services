<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'cinema_id' => $this->cinema_id,
            'cinema' => $this->whenLoaded('cinema', [
                'id' => $this->cinema?->id,
                'name' => $this->cinema?->name,
                'address' => $this->cinema?->address ?? null,
            ]),
            'name' => $this->name,
            'seat_map' => $this->seat_map,
            'total_seats' => $this->total_seats,
            'status' => [
                'code' => $this->status,
                'label' => $this->getStatusLabel($this->status),
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'active' => 'Đang hoạt động',
            'maintenance' => 'Bảo trì',
            'closed' => 'Đã đóng',
            default => 'Không xác định',
        };
    }
}
