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
}
