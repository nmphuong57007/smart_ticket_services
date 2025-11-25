<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContentPost;

class ContentPostSeeder extends Seeder
{
    public function run(): void
    {
        ContentPost::factory()->banner()->count(3)->create();

        ContentPost::factory()->count(5)->create(); // news mặc định

        ContentPost::factory()->promotion()->count(5)->create();

        ContentPost::factory()
            ->count(10)
            ->state(function () {
                return ['type' => fake()->randomElement(['banner', 'news', 'promotion'])];
            })
            ->create();
        $this->command->info('Content Posts seeded thành công!');
    }
}
