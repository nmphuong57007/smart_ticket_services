<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContentPost;

class ContentPostSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Đang seed dữ liệu Content Posts...');

        // Banner
        ContentPost::factory()->banner()->count(4)->create();

        // News
        ContentPost::factory()->news()->count(6)->create();

        // Promotion
        ContentPost::factory()->promotion()->count(5)->create();

        // Random
        ContentPost::factory()->count(10)->create();

        $this->command->info('Seed Content Posts hoàn tất!');
    }
}
