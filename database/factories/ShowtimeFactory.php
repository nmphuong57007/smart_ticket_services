<?php

namespace Database\Factories;

use App\Models\Showtime;
use App\Models\Room;
use App\Models\Movie;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShowtimeFactory extends Factory
{
    protected $model = Showtime::class;

    public function definition()
    {
        $faker = \Faker\Factory::create('vi_VN');

        // ENUM hợp lệ
        $languageTypes = ['sub', 'dub', 'narrated'];
        $formats = ['2D', '3D', 'IMAX', '4DX'];

        // Giờ chiếu cố định
        $showTimes = [
            '08:00:00',
            '10:30:00',
            '13:00:00',
            '15:30:00',
            '18:00:00',
            '20:30:00',
            '22:45:00'
        ];

        // Lấy Room và Movie
        $room = Room::query()->inRandomOrder()->first() ?? Room::factory()->create();
        $movie = Movie::query()->inRandomOrder()->first() ?? Movie::factory()->create();

        return [
            'room_id'       => $room->id,
            'cinema_id'     => $room->cinema_id,
            'movie_id'      => $movie->id,

            'show_date'     => $faker->dateTimeBetween('-3 days', '+10 days')->format('Y-m-d'),
            'show_time'     => $faker->randomElement($showTimes),

            'format'        => $faker->randomElement($formats),
            'language_type' => $faker->randomElement($languageTypes),
            'price'         => $faker->numberBetween(65000, 120000),

            'created_at'    => now(),
            'updated_at'    => now(),
        ];
    }
}
