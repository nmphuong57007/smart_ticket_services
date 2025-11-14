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

        $formats = ['2D', '3D', 'IMAX', '4DX'];
        $statuses = ['coming', 'showing', 'stopped'];
        $languages = ['dub', 'sub', 'narrated'];

        // Ngày chiếu
        $releaseDate = $faker->dateTimeBetween('-1 year', '+3 months');
        $endDate = $faker->dateTimeBetween($releaseDate, '+6 months');

        return [
            'title' => $faker->sentence(3),


            'poster' => 'https://placehold.co/400x600',
            'trailer' => 'https://www.youtube.com/watch?v=jCHv_mLCSJA',


            'description' => $faker->paragraph(5),
            'duration' => $faker->numberBetween(90, 160),
            'format' => $faker->randomElement($formats),
            'language'      => $faker->randomElement($languages),
            'release_date'  => $releaseDate->format('Y-m-d'),
            'end_date'      => $endDate->format('Y-m-d'),
            'status' => $faker->randomElement($statuses),
            'created_at'    => now(),
            'updated_at' => now(),
        ];
    }
}
