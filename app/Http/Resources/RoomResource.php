<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,

            // seat_map: array đã được cast trong Model
            'seat_map'     => $this->seat_map ?? [],

            // Tổng số ghế
            'total_seats'  => $this->total_seats,

            // Thống kê theo loại ghế
            'seat_types' => [
                'vip'    => $this->countSeatsByType('vip'),
                'normal' => $this->countSeatsByType('normal'),
            ],

            // Trạng thái (code + label)
            'status' => [
                'code'  => $this->status,
                'label' => $this->getStatusLabel($this->status),
            ],

            'created_at' => optional($this->created_at)
                ->timezone('Asia/Ho_Chi_Minh')
                ->format('Y-m-d H:i:s'),

            'updated_at' => optional($this->updated_at)
                ->timezone('Asia/Ho_Chi_Minh')
                ->format('Y-m-d H:i:s'),
        ];
    }

    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'active'       => 'Đang hoạt động',
            'maintenance'  => 'Bảo trì',
            'closed'       => 'Đã đóng',
            default        => 'Không xác định',
        };
    }

    private function countSeatsByType(string $type): int
    {
        $map = $this->seat_map ?? [];
        $count = 0;

        foreach ($map as $row) {
            foreach ($row as $seat) {

                // GHẾ DẠNG STRING → LUÔN LÀ GHẾ THƯỜNG
                if (is_string($seat) && $type === 'normal') {
                    $count++;
                }

                // GHẾ DẠNG OBJECT
                if (is_array($seat)) {
                    $seatType = $seat['type'] ?? 'normal';
                    if ($seatType === $type) {
                        $count++;
                    }
                }
            }
        }

        return $count;
    }
}
