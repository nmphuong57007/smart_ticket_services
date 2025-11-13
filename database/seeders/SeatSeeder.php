<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;
use App\Models\Seat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SeatSeeder extends Seeder
{
    public function run(): void
    {
        // Tắt kiểm tra khóa ngoại để truncate
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Seat::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $seatData = [];

        // Lấy tất cả các phòng
        $rooms = Room::with('cinema')->get();

        foreach ($rooms as $room) {
            $seatMap = $room->seat_map ?? [];

            foreach ($seatMap as $row) {
                if (!is_array($row)) continue;

                foreach ($row as $seatCode) {

                    $type = rand(0, 4) === 0 ? 'vip' : 'normal'; // 20% ghế VIP
                    $price = $type === 'vip' ? 120000 : 80000;

                    $seatData[] = [
                        'room_id'    => $room->id,
                        'seat_code'  => $seatCode,
                        'type'       => $type,
                        'status'     => 'available',
                        'price'      => $price,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        // Chia nhỏ insert để tránh lỗi max packet
        foreach (array_chunk($seatData, 5000) as $chunk) {
            DB::table('seats')->insert($chunk);
        }

        $this->command->info('✅ Đã seed ' . count($seatData) . ' ghế thành công cho ' . $rooms->count() . ' phòng.');
    }
}
