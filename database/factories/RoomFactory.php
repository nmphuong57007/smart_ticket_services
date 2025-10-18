<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\Cinema;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition()
    {
        $faker = \Faker\Factory::create('vi_VN');

        return [
            'cinema_id' => Cinema::inRandomOrder()->first()?->id ?? Cinema::factory(),
            'name' => 'Phòng ' . $faker->randomElement(['A', 'B', 'C', 'D', 'E']) . $faker->numberBetween(1, 5),
            'seat_map' => null, // giữ null hoặc bạn có thể tạo seat_map mẫu
            'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
