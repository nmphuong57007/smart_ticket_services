<?php

namespace Database\Factories;

use App\Models\Showtime;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Room;
use App\Models\Movie;

class ShowtimeFactory extends Factory
{
    protected $model = Showtime::class;

    public function definition(): array
    {
        return [
            'movie_id' => Movie::inRandomOrder()->first()->id,
            'room_id' => Room::inRandomOrder()->first()->id,
            'show_date' => $this->faker->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'show_time' => $this->faker->time(),
            'price' => $this->faker->numberBetween(50000, 200000),
            'format' => $this->faker->randomElement(['2D', '3D', 'IMAX', '4DX']),
        ];
    }
}
