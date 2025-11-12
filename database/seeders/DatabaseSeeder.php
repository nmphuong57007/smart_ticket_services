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
            AdminUserSeeder::class,
            CinemasSeeder::class,
            RoomsSeeder::class,
            GenresSeeder::class,
            MoviesSeeder::class,
            ShowtimesSeeder::class,
            SeatSeeder::class,
            SeatReservationsSeeder::class,
            PointsHistorySeeder::class,
            ComboSeeder::class,
            ContentsSeeder::class,
        ]);

        // Bật lại foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
