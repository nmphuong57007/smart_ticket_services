<?php

namespace Database\Factories;

use App\Models\Cinema;
use Illuminate\Database\Eloquent\Factories\Factory;

class CinemaFactory extends Factory
{
    protected $model = Cinema::class;

    public function definition()
    {
        $faker = \Faker\Factory::create('vi_VN');

        return [
            'name' => 'Rạp ' . $faker->company,    // Tên rạp
            'address' => $faker->address,          // Địa chỉ
            'phone' => $faker->phoneNumber,        // Số điện thoại
            'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
