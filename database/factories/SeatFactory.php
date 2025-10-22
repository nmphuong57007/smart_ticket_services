<?php

namespace Database\Factories;

use App\Models\Seat;
use App\Models\Showtime;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeatFactory extends Factory
{
    protected $model = Seat::class;

    public function definition()
    {
        return [
            'showtime_id' => Showtime::factory(), // tự tạo 1 showtime nếu chưa có
            'seat_code' => $this->faker->unique()->bothify('?#'), // A1, B2...
            'type' => $this->faker->randomElement(['normal', 'vip']),
            'status' => $this->faker->randomElement(['available', 'booked']),
        ];
    }
}
