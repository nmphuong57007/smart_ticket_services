<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cinema;

class CinemasSeeder extends Seeder
{
    public function run(): void
    {
        Cinema::factory()->count(5)->create();
    }
}
