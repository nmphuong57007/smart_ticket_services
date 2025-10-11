<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomsSeeder extends Seeder
{
    public function run(): void
    {
        Room::factory()->count(10)->create();
    }
}
