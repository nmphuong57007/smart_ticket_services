<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cinema;
use Illuminate\Support\Facades\DB;

class CinemasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Xóa dữ liệu cũ an toàn hơn (tránh lỗi khóa ngoại)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Cinema::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Tạo dữ liệu giả theo cấu hình
        $count = (int) config('seeder.cinemas', 30) * (int) config('seeder.multiplier', 1);

        Cinema::factory()
            ->count($count)
            ->create();

        $this->command->info("Đã tạo {$count} rạp phim thành công!");
    }
}
