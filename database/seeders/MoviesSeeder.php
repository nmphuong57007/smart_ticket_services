<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Movie;

class MoviesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Movie::query()->delete();

        Movie::factory()
            ->count(20) // Tạo 20 phim mẫu
            ->create();
    }
}
