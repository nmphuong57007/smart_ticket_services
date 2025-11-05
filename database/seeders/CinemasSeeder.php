<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cinema;

class CinemasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Cinema::query()->delete();

        Cinema::factory()
            ->count(30) // Tạo 5 rạp phim
            ->create();
    }
}
