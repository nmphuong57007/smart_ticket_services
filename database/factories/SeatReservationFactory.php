<?php

namespace Database\Factories;

use App\Models\SeatReservation;
use App\Models\Showtime;
use App\Models\User;
use App\Models\Seat;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class SeatReservationFactory extends Factory
{
    protected $model = SeatReservation::class;

    public function definition(): array
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh');

        // Lấy random showtime
        $showtime = Showtime::inRandomOrder()->first();
        if (!$showtime) {
            $showtime = Showtime::factory()->create();
        }

        // Lấy ghế thuộc đúng phòng của showtime
        $seat = Seat::where('room_id', $showtime->room_id)->inRandomOrder()->first();
        if (!$seat) {
            $seat = Seat::factory()->create(['room_id' => $showtime->room_id]);
        }

        // Lấy user bất kỳ
        $user = User::inRandomOrder()->first() ?? User::factory()->create();

        $status = $this->faker->randomElement([
            SeatReservation::STATUS_RESERVED,
            SeatReservation::STATUS_BOOKED,
        ]);

        $reservedAt = $now->copy()->subMinutes(rand(1, 15));
        $bookedAt = $status === SeatReservation::STATUS_BOOKED
            ? $reservedAt->copy()->addMinutes(rand(1, 3))
            : null;

        return [
            'showtime_id' => $showtime->id,
            'seat_id'     => $seat->id,
            'user_id'     => $user->id,
            'status'      => $status,
            'reserved_at' => $reservedAt,
            'booked_at'   => $bookedAt,
            'created_at'  => $now,
            'updated_at'  => $now,
        ];
    }
}
