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
            AdminUserSeeder::class,       // 1️ Tạo tài khoản admin
            CinemasSeeder::class,         // 2️ Tạo danh sách rạp
            RoomsSeeder::class,           // 3️ Mỗi rạp có vài phòng
            GenresSeeder::class,          // 4️ Tạo thể loại phim
            MoviesSeeder::class,          // 5️ Tạo phim
            ShowtimesSeeder::class,       // 6️ Lịch chiếu liên kết phim + phòng
            SeatSeeder::class,            // 7️ Ghế thuộc từng phòng
            PointsHistorySeeder::class,   // 8️ Lịch sử điểm thưởng
            ComboSeeder::class,           // 9️ Combo thức ăn/nước
            ContentsSeeder::class,        // 10 Bài viết, tin tức
        ]);

        // Bật lại kiểm tra khóa ngoại
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info("✅ Database đã được seed đầy đủ thành công!");
    }
}
