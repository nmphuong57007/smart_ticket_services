<?php

namespace Database\Factories;

use App\Models\Cinema;
use Illuminate\Database\Eloquent\Factories\Factory;

class CinemaFactory extends Factory
{
    protected $model = Cinema::class;

    public function definition(): array
    {
        return [
            'name' => 'Cinema ' . $this->faker->unique()->numberBetween(1, 10),
            'address' => $this->faker->address,
        ];
    }
}
