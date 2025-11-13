<?php

namespace Database\Seeders;

use App\Models\Content;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Tắt foreign key checks để tăng tốc seeding
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

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
          
        ]);

        // Bật lại foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
