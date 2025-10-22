<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,  // tạo user trước
            CinemasSeeder::class,      // tạo cinema trước rooms
            RoomsSeeder::class,      // tạo phòng trước
            MoviesSeeder::class,     // tạo phim trước
            ShowtimesSeeder::class,  // tạo lịch chiếu sau khi có phòng & phim
            SeatSeeder::class,      // tạo ghế sau khi có lịch chiếu
            PointsHistorySeeder::class, // tạo lịch sử điểm
            ComboSeeder::class,      // tạo combo
        ]);
    }
}
