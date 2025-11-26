<?php

namespace App\Http\Services\Promotion;

use App\Models\Promotion;
use Illuminate\Support\Facades\DB;

class PromotionService
{
    /**
     * Lấy danh sách mã giảm giá có filter + sort + pagination
     */
    public function getList(array $filters)
    {
        $query = Promotion::query();

        // Lọc theo trạng thái: active | expired
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Tìm theo code
        if (!empty($filters['code'])) {
            $query->where('code', 'LIKE', '%' . $filters['code'] . '%');
        }

        // Sắp xếp
        $sortBy = $filters['sort_by'] ?? 'id';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $filters['per_page'] ?? 10;

        return $query->paginate($perPage);
    }


    /**
     * Tạo một mã giảm giá mới
     */
    public function create(array $data): Promotion
    {
        return DB::transaction(fn() => Promotion::create($data));
    }


    /**
     * Cập nhật mã giảm giá
     */
    public function update(Promotion $promotion, array $data): Promotion
    {
        DB::transaction(fn() => $promotion->update($data));

        // Sau update, kiểm tra hạn
        $promotion->updateStatusIfExpired();

        return $promotion;
    }


    /**
     * Vô hiệu hóa mã giảm giá (không xóa)
     */
    public function disable(Promotion $promotion): Promotion
    {
        DB::transaction(
            fn() =>
            $promotion->update(['status' => Promotion::STATUS_EXPIRED])
        );

        return $promotion;
    }


    /**
     * Áp dụng mã giảm giá cho người dùng
     */
    public function apply(string $code): array
    {
        $promotion = Promotion::where('code', $code)->first();

        if (!$promotion) {
            return [
                'valid' => false,
                'message' => 'Mã giảm giá không tồn tại'
            ];
        }

        // Cập nhật trạng thái nếu hết hạn
        $promotion->updateStatusIfExpired();

        if (!$promotion->isValid()) {
            return [
                'valid' => false,
                'message' => 'Mã đã hết hạn hoặc không khả dụng'
            ];
        }

        return [
            'valid' => true,
            'discount_percent' => $promotion->discount_percent,
            'message' => 'Áp dụng mã thành công'
        ];
    }
}
