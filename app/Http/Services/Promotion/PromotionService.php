<?php

namespace App\Http\Services\Promotion;

use App\Models\Promotion;
use Illuminate\Support\Facades\DB;

class PromotionService
{
    // ====================
    // LẤY DANH SÁCH
    // ====================
    public function getList(array $filters)
    {
        $query = Promotion::query();

        // filter theo status DB (active / disabled)
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['code'])) {
            $query->where('code', 'LIKE', '%' . $filters['code'] . '%');
        }

        $sortBy = $filters['sort_by'] ?? 'id';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($filters['per_page'] ?? 10);
    }

    // ====================
    // TẠO MÃ GIẢM GIÁ
    // ====================
    public function create(array $data): Promotion
    {
        return DB::transaction(fn() => Promotion::create($data));
    }

    // ====================
    // CẬP NHẬT
    // ====================
    public function update(Promotion $promotion, array $data): Promotion
    {
        DB::transaction(fn() => $promotion->update($data));

        return $promotion;
    }

    // ====================
    // VÔ HIỆU HÓA
    // ====================
    public function disable(Promotion $promotion): Promotion
    {
        DB::transaction(
            fn() => $promotion->update(['status' => Promotion::STATUS_DISABLED])
        );

        return $promotion;
    }


    // ================================
    // ÁP DỤNG MÃ GIẢM GIÁ
    // ================================
    public function apply(string $code, int $movieId, int $totalAmount): array
    {
        $promotion = Promotion::where('code', $code)->first();

        if (!$promotion) {
            return [
                'valid' => false,
                'message' => 'Mã giảm giá không tồn tại',
            ];
        }

        // -----------------
        // 1. Kiểm tra hợp lệ
        // -----------------
        if (!$promotion->is_valid) {
            return [
                'valid' => false,
                'message' => 'Mã không còn hợp lệ',
            ];
        }

        // -----------------
        // 2. Đơn hàng tối thiểu
        // -----------------
        if ($promotion->min_order_amount && $totalAmount < $promotion->min_order_amount) {
            return [
                'valid' => false,
                'message' => 'Đơn hàng không đủ điều kiện áp dụng mã',
            ];
        }

        // -----------------
        // 3. Mã áp dụng theo phim
        // -----------------
        if ($promotion->movie_id !== null && $promotion->movie_id !== $movieId) {
            return [
                'valid' => false,
                'message' => 'Mã giảm giá không áp dụng cho phim này',
            ];
        }

        // -----------------
        // 4. Tính số tiền giảm
        // -----------------
        $discount = 0;

        if ($promotion->type === 'percent') {
            $discount = intval($totalAmount * ($promotion->discount_percent / 100));

            if ($promotion->max_discount_amount) {
                $discount = min($discount, $promotion->max_discount_amount);
            }
        } else {
            $discount = intval($promotion->discount_amount);
        }

        $discount = min($discount, $totalAmount);

        return [
            'valid'          => true,
            'discount_value' => $discount,
            'final_amount'   => $totalAmount - $discount,
            'message'        => 'Áp dụng mã giảm giá thành công',
        ];
    }
}
