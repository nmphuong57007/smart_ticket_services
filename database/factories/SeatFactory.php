<?php

namespace Database\Factories;

use App\Models\Seat;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeatFactory extends Factory
{
    protected $model = Seat::class;

    public function definition(): array
    {
        $room = Room::inRandomOrder()->first() ?? Room::factory()->create();

        $type = $this->faker->randomElement(['normal', 'vip']);
        $price = $type === 'vip'
            ? $this->faker->numberBetween(100000, 150000)
            : $this->faker->numberBetween(70000, 90000);

        return [
            'cinema_id'  => $room->cinema_id,
            'room_id'    => $room->id,
            'seat_code'  => $this->faker->unique()->bothify('??##'),
            'type'       => $type,
            'status'     => $this->faker->randomElement(['available', 'booked']),
            'price'      => $price,
            'created_at' => now('Asia/Ho_Chi_Minh'),
            'updated_at' => now('Asia/Ho_Chi_Minh'),
        ];
    }
}
