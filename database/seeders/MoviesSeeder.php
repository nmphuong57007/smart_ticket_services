<?php

namespace Database\Seeders;

use App\Models\Movie;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MoviesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tạo 50 bản ghi phim mẫu
        Movie::factory()
            ->count(50) // Số lượng bản ghi muốn tạo
            ->create();
    }
}
