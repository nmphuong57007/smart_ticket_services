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

 feat/promotions_post
            AdminUserSeeder::class,  // tạo user trước
            CinemasSeeder::class,      // tạo cinema trước rooms
           
            MoviesSeeder::class,     // tạo phim trước
            ShowtimesSeeder::class,  // tạo lịch chiếu sau khi có phòng & phim
            SeatSeeder::class,      // tạo ghế sau khi có lịch chiếu
            PointsHistorySeeder::class, // tạo lịch sử điểm
            ComboSeeder::class,      // tạo combo
            ContentsSeeder::class,   // tạo news
            ProductCategorySeeder::class, // tạo category trước products
            ProductSeeder::class,    // tạo products sau khi có category

            RoomsSeeder::class,

            GenresSeeder::class,
feat/promotions_post
          

            MoviesSeeder::class,
            ShowtimesSeeder::class,
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
