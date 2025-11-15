<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;
use App\Models\Cinema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RoomsSeeder extends Seeder
{
    public function run(): void
    {
        // Xóa phòng cũ
        Room::query()->delete();

        // Lấy toàn bộ rạp hiện có
        $cinemas = Cinema::all();

        $seatMap = [
            ['A1', 'A2', 'A3', 'A4', 'A5', 'A6', 'A7', 'A8'],
            ['B1', 'B2', 'B3', 'B4', 'B5', 'B6', 'B7', 'B8'], 
            ['C1', 'C2', 'C3', 'C4', 'C5', 'C6', 'C7', 'C8'],
            ['D1', 'D2', 'D3', 'D4', 'D5', 'D6', 'D7', 'D8'],
            ['E1', 'E2', 'E3', 'E4', 'E5', 'E6', 'E7', 'E8'],
        ];

        // Tạo dữ liệu hàng loạt thay vì tạo model từng cái để tránh overhead của Eloquent events
        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $roomsData = [];

        // Mỗi rạp có số phòng theo cấu hình
        $roomsPerCinema = (int) config('seeder.rooms_per_cinema', 5) * (int) config('seeder.multiplier', 1);
        foreach ($cinemas as $cinema) {
            for ($i = 1; $i <= $roomsPerCinema; $i++) {
                // Tính tổng ghế từ seatMap (đơn giản: tổng số phần tử trong mỗi hàng)
                $totalSeats = 0;
                foreach ($seatMap as $row) {
                    if (!is_array($row)) continue;
                    $totalSeats += count($row);
                }

                $roomsData[] = [
                    'cinema_id' => $cinema->id,
                    'name' => 'Phòng ' . chr(64 + ($i)),
                    'seat_map' => json_encode($seatMap),
                    'total_seats' => $totalSeats,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Chia nhỏ insert nếu cần
        foreach (array_chunk($roomsData, 500) as $chunk) {
            DB::table('rooms')->insert($chunk);
        }
    }
}

