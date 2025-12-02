<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Đang seed dữ liệu sản phẩm (combo, food, drink)...');

        // 5 combo
        Product::factory()->combo()->count(5)->create();

        // 5 food
        Product::factory()->food()->count(5)->create();

        // 5 drink
        Product::factory()->drink()->count(5)->create();

        $this->command->info('Seed Product hoàn tất!');
    }
}
