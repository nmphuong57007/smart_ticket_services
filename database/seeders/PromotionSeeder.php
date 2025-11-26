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

        Promotion::create([
            'code' => 'GIAM20',
            'discount_percent' => 20,
            'start_date' => $now->copy()->subDays(3)->format('Y-m-d'),
            'end_date' => $now->copy()->addDays(7)->format('Y-m-d'),
            'status' => Promotion::STATUS_ACTIVE,
        ]);

        Promotion::create([
            'code' => 'BLACKFRIDAY',
            'discount_percent' => 50,
            'start_date' => $now->copy()->subDays(1)->format('Y-m-d'),
            'end_date' => $now->copy()->addDays(2)->format('Y-m-d'),
            'status' => Promotion::STATUS_ACTIVE,
        ]);

        Promotion::create([
            'code' => 'HETHANG',
            'discount_percent' => 10,
            'start_date' => $now->copy()->subDays(10)->format('Y-m-d'),
            'end_date' => $now->copy()->subDays(5)->format('Y-m-d'),
            'status' => Promotion::STATUS_EXPIRED,
        ]);

        $this->command->info('PromotionSeeder đã seed dữ liệu mẫu thành công!');
    }
}
