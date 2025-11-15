<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Tạm tắt kiểm tra khóa ngoại để tránh lỗi khi truncate
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Gọi các seeder theo thứ tự logic (quan hệ phụ thuộc)
        $this->call([
            AdminUserSeeder::class,
            CinemasSeeder::class,
            RoomsSeeder::class,
            GenresSeeder::class,
            MoviesSeeder::class,
            ShowtimesSeeder::class,
            SeatSeeder::class,
            UsersSeeder::class,
            PointsHistorySeeder::class,
            ComboSeeder::class,
            ContentsSeeder::class,       
        ]);

        // Bật lại kiểm tra khóa ngoại
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info("✅ Database đã được seed đầy đủ thành công!");
    }
}
