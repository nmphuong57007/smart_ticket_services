<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Promotion;
use Carbon\Carbon;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh');

        // 1. Mã giảm theo % - đang active
        Promotion::create([
            'code' => 'GIAM20',
            'type' => 'percent',
            'discount_percent' => 20,
            'max_discount_amount' => 30000, // tối đa giảm 30k
            'usage_limit' => 100,
            'used_count' => 0,
            'movie_id' => null, // áp dụng mọi phim
            'min_order_amount' => 50000,
            'start_date' => $now->copy()->subDays(3)->format('Y-m-d'),
            'end_date'   => $now->copy()->addDays(7)->format('Y-m-d'),
            'status' => Promotion::STATUS_ACTIVE,
        ]);

        // 2. Mã giảm theo tiền cố định
        Promotion::create([
            'code' => 'GIAM50000',
            'type' => 'money',
            'discount_amount' => 50000,
            'usage_limit' => 50,
            'used_count' => 10,
            'movie_id' => null,
            'min_order_amount' => 100000,
            'start_date' => $now->copy()->subDays(1)->format('Y-m-d'),
            'end_date'   => $now->copy()->addDays(5)->format('Y-m-d'),
            'status' => Promotion::STATUS_ACTIVE,
        ]);

        // 3. Mã giảm giá riêng cho phim ID = 1
        Promotion::create([
            'code' => 'MOVIE10',
            'type' => 'percent',
            'discount_percent' => 10,
            'max_discount_amount' => 20000,
            'usage_limit' => 200,
            'used_count' => 0,
            'movie_id' => 1, // áp dụng riêng cho phim ID=1
            'min_order_amount' => 0,
            'start_date' => $now->copy()->subDays(2)->format('Y-m-d'),
            'end_date'   => $now->copy()->addDays(10)->format('Y-m-d'),
            'status' => Promotion::STATUS_ACTIVE,
        ]);

        // 4. Mã chưa tới ngày áp dụng (upcoming)
        Promotion::create([
            'code' => 'SALECOMING',
            'type' => 'percent',
            'discount_percent' => 30,
            'max_discount_amount' => 40000,
            'usage_limit' => 1000,
            'used_count' => 0,
            'movie_id' => null,
            'min_order_amount' => 50000,
            'start_date' => $now->copy()->addDays(2)->format('Y-m-d'),
            'end_date'   => $now->copy()->addDays(10)->format('Y-m-d'),
            'status' => Promotion::STATUS_UPCOMING,
        ]);

        // 5. Mã đã hết hạn
        Promotion::create([
            'code' => 'HETHANG',
            'type' => 'percent',
            'discount_percent' => 10,
            'max_discount_amount' => 15000,
            'usage_limit' => 10,
            'used_count' => 10,
            'movie_id' => null,
            'min_order_amount' => 0,
            'start_date' => $now->copy()->subDays(10)->format('Y-m-d'),
            'end_date'   => $now->copy()->subDays(5)->format('Y-m-d'),
            'status' => Promotion::STATUS_EXPIRED,
        ]);

        // 6. Mã bị admin tắt (disabled)
        Promotion::create([
            'code' => 'TAMKHOA',
            'type' => 'percent',
            'discount_percent' => 15,
            'max_discount_amount' => 25000,
            'usage_limit' => 999,
            'used_count' => 0,
            'movie_id' => null,
            'min_order_amount' => 0,
            'start_date' => $now->copy()->subDays(1)->format('Y-m-d'),
            'end_date'   => $now->copy()->addDays(5)->format('Y-m-d'),
            'status' => Promotion::STATUS_DISABLED,
        ]);

        $this->command->info('PromotionSeeder đã seed dữ liệu nâng cấp thành công!');
    }
}
