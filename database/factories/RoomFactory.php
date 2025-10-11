<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Room;
use App\Models\Cinema;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'cinema_id' => Cinema::inRandomOrder()->first()->id, // đảm bảo tồn tại
            'name' => 'Phòng ' . $this->faker->numberBetween(1, 10),
            'seat_map' => json_encode($this->faker->randomElements(range(1, 50), 30)),
        ];
    }
}
