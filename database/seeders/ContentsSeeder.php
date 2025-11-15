<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Content;

class ContentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Content::query()->delete();

        $count = (int) config('seeder.contents', 20) * (int) config('seeder.multiplier', 1);

        Content::factory()
            ->count($count)
            ->create();
    }
}
