<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SeatReservation;
use App\Models\Showtime;
use App\Models\User;
use App\Models\Seat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SeatReservationsSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh');

        // ✅ Xóa dữ liệu cũ an toàn
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        SeatReservation::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $users = User::whereIn('role', ['customer', 'admin', 'staff'])->get();
        $showtimes = Showtime::all();
        $seats = Seat::all();

        if ($showtimes->isEmpty() || $seats->isEmpty() || $users->isEmpty()) {
            $this->command->warn('⚠️ Thiếu dữ liệu showtime / seat / user. Hãy seed đầy đủ trước!');
            return;
        }

        $reservations = [];

        // ✅ Tạo 100 bản ghi ngẫu nhiên hợp lệ
        for ($i = 0; $i < 100; $i++) {
            // chọn 1 suất chiếu và tìm ghế trong đúng phòng chiếu đó
            $showtime = $showtimes->random();
            $roomSeats = $seats->where('room_id', $showtime->room_id);

            // bỏ qua nếu phòng chưa có ghế
            if ($roomSeats->isEmpty()) continue;

            $seat = $roomSeats->random();
            $user = $users->random();

            $status = fake()->randomElement([
                SeatReservation::STATUS_RESERVED,
                SeatReservation::STATUS_BOOKED,
            ]);

            $reservedAt = $now->copy()->subMinutes(rand(1, 15));

            $reservations[] = [
                'showtime_id' => $showtime->id,
                'seat_id'     => $seat->id,
                'user_id'     => $user->id,
                'status'      => $status,
                'reserved_at' => $reservedAt,
                'booked_at'   => $status === SeatReservation::STATUS_BOOKED
                    ? $reservedAt->copy()->addMinutes(rand(1, 3))
                    : null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }

        foreach (array_chunk($reservations, 500) as $chunk) {
            SeatReservation::insert($chunk);
        }

        $this->command->info('🎟️ Đã seed ' . count($reservations) . ' bản ghi giữ/đặt ghế thành công!');
    }
}
