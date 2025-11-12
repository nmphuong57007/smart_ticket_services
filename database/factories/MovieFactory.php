<?php

namespace Database\Factories;

use App\Models\Movie;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;


class MovieFactory extends Factory
{
    protected $model = Movie::class;

    public function definition()
    {
        $faker = \Faker\Factory::create('vi_VN');

        $genres = ['Hành động', 'Khoa học viễn tưởng', 'Hài', 'Kinh dị', 'Lãng mạn', 'Phiêu lưu', 'Hoạt hình', 'Chiến tranh'];
        $formats = ['2D', '3D', 'IMAX', '4DX'];
        $statuses = ['coming', 'showing', 'stopped'];

        $chosenGenres = $faker->randomElements($genres, 2);

        return [
            'title' => $faker->sentence(3),

            'poster' => 'https://placehold.co/600x400',
            'trailer' => 'https://www.youtube.com/watch?v=jCHv_mLCSJA',

            'description' => $faker->paragraph(5),
            'duration' => $faker->numberBetween(90, 160),
            'format' => $faker->randomElement($formats),
            'release_date' => $faker->dateTimeBetween('-1 year', '+3 months')->format('Y-m-d'),
            'status' => $faker->randomElement($statuses),
            'created_at' => $faker->dateTimeBetween('-1 year', 'now')
        ];
    }
}
