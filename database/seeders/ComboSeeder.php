<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ComboSeeder extends Seeder
{
    public function run(): void
    {
        $combos = [
            [
                'name' => 'Combo Bắp Nhỏ + Nước 330ml',
                'price' => 45000,
                'description' => 'Bắp nhỏ + nước 330ml, gói muối tặng kèm.',
                'image' => 'https://placehold.co/400x600',
                'stock' => 100,
            ],
            [
                'name' => 'Combo Bắp Vừa + Nước 400ml',
                'price' => 55000,
                'description' => 'Bắp vừa + nước 400ml, thêm gói bơ.',
                'image' => 'https://placehold.co/400x600',
                'stock' => 90,
            ],
            [
                'name' => 'Combo Bắp Lớn + Nước 500ml',
                'price' => 65000,
                'description' => 'Bắp lớn thơm ngon + nước 500ml.',
                'image' => 'https://placehold.co/400x600',
                'stock' => 80,
            ],
            // Thêm combo khác nếu muốn
        ];

        foreach ($combos as $combo) {
            Product::create(array_merge($combo, [
                'type' => 'combo',
                'is_active' => true,
            ]));
        }
    }
}
