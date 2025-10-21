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

        // Các giá trị hợp lệ với ENUM trong DB
        $languageTypes = ['sub', 'dub', 'narrated'];
        $formats = ['2D', '3D', 'IMAX', '4DX'];

        // Các khung giờ phổ biến trong rạp
        $showTimes = [
            '08:00:00', '10:30:00', '13:00:00', '15:30:00', '18:00:00', '20:30:00', '22:45:00'
        ];

        return [
            'room_id' => Room::inRandomOrder()->first()?->id ?? Room::factory(),
            'movie_id' => Movie::inRandomOrder()->first()?->id ?? Movie::factory(),
            'show_date' => $faker->dateTimeBetween('-3 days', '+10 days')->format('Y-m-d'),
            'show_time' => $faker->randomElement($showTimes),
            'format' => $faker->randomElement($formats),
            'language_type' => $faker->randomElement($languageTypes), // Sửa lại đúng ENUM
            'price' => $faker->numberBetween(65000, 120000),
            'created_at' => $faker->dateTimeBetween('-3 months', 'now')
        ];
    }
}
