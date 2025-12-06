<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // fallback chống null
        $used = $this->used_count ?? 0;
        $limit = $this->usage_limit;

        return [

            // Thông tin cơ bản
            'id'                => $this->id,
            'code'              => $this->code,

            // Loại giảm giá
            'type'              => $this->type,
            'discount_percent'  => $this->discount_percent,
            'discount_amount'   => $this->discount_amount,
            'max_discount_amount' => $this->max_discount_amount,

            // Giới hạn lượt dùng
            'usage_limit'       => $limit,
            'used_count'        => $used,

            // Tính remaining an toàn
            'remaining'         => $limit !== null ? max(0, $limit - $used) : null,

            // Áp dụng theo phim
            'movie_id'          => $this->movie_id,
            'apply_for_all_movies' => $this->movie_id === null,

            // Điều kiện tối thiểu
            'min_order_amount'  => $this->min_order_amount,

            // Ngày áp dụng
            'start_date'        => $this->start_date?->format('Y-m-d'),
            'end_date'          => $this->end_date?->format('Y-m-d'),

            // Trạng thái runtime
            'status'            => $this->runtime_status,
            'status_label'      => $this->runtime_status_label,

            // Kiểm tra hợp lệ
            'is_valid'          => $this->is_valid,
            'is_expired'        => $this->is_expired,

            // Timestamps
            'created_at' => optional($this->created_at)
                ->timezone('Asia/Ho_Chi_Minh')
                ->format('Y-m-d H:i:s'),

            'updated_at' => optional($this->updated_at)
                ->timezone('Asia/Ho_Chi_Minh')
                ->format('Y-m-d H:i:s'),
        ];
    }
}
