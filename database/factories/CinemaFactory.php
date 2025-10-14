<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Cinema;

class CinemaFactory extends Factory
{
    protected $model = Cinema::class;

    public function definition(): array
    {
        return [
            'name' => 'Rạp ' . $this->faker->company(),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
        ];
    }
}
