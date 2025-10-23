<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Showtime;
use App\Models\Seat;
use Illuminate\Support\Facades\DB;

class SeatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = ['A', 'B', 'C', 'D', 'E']; // Hàng ghế
        $cols = range(1, 8);                // Cột ghế

        // Lấy tất cả showtime
        $showtimes = Showtime::all();

        $seatData = [];

        foreach ($showtimes as $showtime) {
            foreach ($rows as $row) {
                foreach ($cols as $col) {
                    $seatData[] = [
                        'showtime_id' => $showtime->id,
                        'seat_code' => $row . $col,               // A1, A2, B1,...
                        'type' => rand(0, 4) === 0 ? 'vip' : 'normal', // 20% vip
                        'status' => rand(0, 5) === 0 ? 'booked' : 'available', // 1/6 ghế đã đặt
                    ];
                }
            }
        }

        // Bulk insert với chunking để tăng tốc
        $chunks = array_chunk($seatData, 5000);
        foreach ($chunks as $chunk) {
            DB::table('seats')->insert($chunk);
        }

        $this->command->info('Seats seeded successfully!');
    }
}
