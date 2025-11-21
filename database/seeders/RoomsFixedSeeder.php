<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;
use Carbon\Carbon;

class RoomsFixedSeeder extends Seeder
{
    public function run(): void
    {
        Room::truncate();

        $now = Carbon::now('Asia/Ho_Chi_Minh');

        $seatMap = [
            // A — thường
            array_map(fn($n) => ['code' => "A$n", 'type' => 'normal'], range(1, 8)),

            // B — thường
            array_map(fn($n) => ['code' => "B$n", 'type' => 'normal'], range(1, 8)),

            // C — VIP
            array_map(fn($n) => ['code' => "C$n", 'type' => 'vip'], range(1, 8)),

            // D — VIP
            array_map(fn($n) => ['code' => "D$n", 'type' => 'vip'], range(1, 8)),

            // E — thường
            array_map(fn($n) => ['code' => "E$n", 'type' => 'normal'], range(1, 8)),
        ];


        for ($i = 1; $i <= 5; $i++) {
            Room::create([
                'cinema_id'  => 1,
                'name'       => "Phòng $i",
                'seat_map'   => $seatMap,
                'status'     => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
