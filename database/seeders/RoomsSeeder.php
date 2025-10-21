<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;
use App\Models\Cinema;

class RoomsSeeder extends Seeder
{
    public function run(): void
    {
        // Xóa phòng cũ
        Room::query()->delete();

        // Lấy toàn bộ rạp hiện có
        $cinemas = Cinema::all();

        foreach ($cinemas as $cinema) {
            // Mỗi rạp có 5 phòng chiếu
            Room::factory()
                ->count(5)
                ->create([
                    'cinema_id' => $cinema->id,
                ]);
        }
    }
}

