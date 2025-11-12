<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;
use Illuminate\Support\Facades\DB;

class SeatSeeder extends Seeder
{
    public function run(): void
    {
        $seatData = [];

        // Lấy tất cả các phòng
        $rooms = Room::all();

        foreach ($rooms as $room) {
            // thanks to $casts, $room->seat_map luôn là array
            $seatMap = $room->seat_map;

            foreach ($seatMap as $row) {
                if (!is_array($row)) continue;

                foreach ($row as $seatCode) {
                    $seatData[] = [
                        'room_id'   => $room->id,
                        'seat_code' => $seatCode,
                        'type'      => rand(0, 4) === 0 ? 'vip' : 'normal', // 20% VIP
                        'status'    => 'available',
                        'price'     => $room->seat_price ?? 0,
                    ];
                }
            }
        }

        // Bulk insert để tối ưu
        foreach (array_chunk($seatData, 5000) as $chunk) {
            DB::table('seats')->insert($chunk);
        }

        $this->command->info('Seats seeded successfully from seat_map!');
    }
}
