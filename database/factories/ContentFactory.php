<?php

namespace Database\Factories;

use App\Models\Content;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContentFactory extends Factory
{
    protected $model = Content::class;

    public function definition()
    {
        $faker = \Faker\Factory::create('vi_VN');
        $types = ['banner', 'news'];

        return [
            'type' => $faker->randomElement($types), // Loại nội dung                  
            'title' => $faker->sentence(3),          // Tiêu đề
            'image' => 'https://picsum.photos/400/600?random=' . $faker->unique()->numberBetween(1, 9999),
            'description' => $faker->paragraph(5), // Mô tả nội dung
            'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
