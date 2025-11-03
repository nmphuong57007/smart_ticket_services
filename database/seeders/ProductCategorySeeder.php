<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('product_categories')->insert([
            ['name' => 'Bắp', 'description' => 'Các loại bắp rang bơ'],
            ['name' => 'Nước', 'description' => 'Các loại nước giải khát'],
            ['name' => 'Combo', 'description' => 'Gói combo bắp + nước'],
        ]);
    }
}
