<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Showtime;

class ShowtimesSeeder extends Seeder
{
    public function run(): void
    {
        Showtime::factory()->count(50)->create();
    }
}
