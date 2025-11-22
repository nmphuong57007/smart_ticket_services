<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SeatResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'showtime_id' => $this->showtime_id,

            // seat_code giữ nguyên A1, B4...
            'seat_code'   => $this->seat_code,

            // Loại ghế
            'type'        => $this->type,

            // Trạng thái ghế
            'status'      => $this->status,

            'label'       => $this->getStatusLabel(),

            // Giá ghế theo suất chiếu
            'price'       => (float) $this->price,

            // Thời gian
            'created_at'  => optional($this->created_at)
                ->timezone('Asia/Ho_Chi_Minh')
                ->format('Y-m-d H:i:s'),
            'updated_at'  => optional($this->updated_at)
                ->timezone('Asia/Ho_Chi_Minh')
                ->format('Y-m-d H:i:s'),
        ];
    }

    private function getStatusLabel(): string
    {
        return match ($this->status) {
            'available'   => 'Còn trống',
            'booked'      => 'Đã đặt',
            'selected'    => 'Đang chọn',
            'unavailable' => 'Không sử dụng', 
            default       => 'Không xác định',
        };
    }
}
