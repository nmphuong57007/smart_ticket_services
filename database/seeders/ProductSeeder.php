<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // Xóa dữ liệu cũ (cẩn thận khi đang dev)
        DB::table('combo_items')->truncate();
        DB::table('products')->truncate();

        // === Nhóm BẮP ===
        $baps = [
            [
                'name' => 'Bắp bơ lớn',
                'price' => 45000,
                'stock' => 100,
                'category_id' => 1, // Bắp
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Bắp caramel',
                'price' => 50000,
                'stock' => 80,
                'category_id' => 1,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ];

        // === Nhóm NƯỚC ===
        $nuocs = [
            [
                'name' => 'Pepsi lon',
                'price' => 30000,
                'stock' => 150,
                'category_id' => 2, // Nước
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => '7Up lon',
                'price' => 28000,
                'stock' => 150,
                'category_id' => 2,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Coca Cola chai',
                'price' => 35000,
                'stock' => 100,
                'category_id' => 2,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ];

        // Insert sản phẩm đơn
        DB::table('products')->insert(array_merge($baps, $nuocs));

        // Lấy ID sản phẩm để dùng cho combo
        $bapBo = DB::table('products')->where('name', 'Bắp bơ lớn')->first();
        $bapCaramel = DB::table('products')->where('name', 'Bắp caramel')->first();
        $pepsi = DB::table('products')->where('name', 'Pepsi lon')->first();
        $up7 = DB::table('products')->where('name', '7Up lon')->first();

        // === Nhóm COMBO ===
        $combos = [
            [
                'name' => 'Combo Bắp bơ + Pepsi',
                'price' => 75000,
                'stock' => 0,
                'category_id' => 3, // Combo
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Combo Bắp Caramel + 7Up',
                'price' => 78000,
                'stock' => 0,
                'category_id' => 3,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ];

        DB::table('products')->insert($combos);

        // Lấy ID combo
        $combo1 = DB::table('products')->where('name', 'Combo Bắp bơ + Pepsi')->first();
        $combo2 = DB::table('products')->where('name', 'Combo Bắp Caramel + 7Up')->first();

        // === Bảng combo_items ===
        DB::table('combo_items')->insert([
            // Combo 1: Bắp bơ + Pepsi
            [
                'combo_id' => $combo1->id,
                'product_id' => $bapBo->id,
                'quantity' => 1,
            ],
            [
                'combo_id' => $combo1->id,
                'product_id' => $pepsi->id,
                'quantity' => 1,
            ],
            // Combo 2: Bắp Caramel + 7Up
            [
                'combo_id' => $combo2->id,
                'product_id' => $bapCaramel->id,
                'quantity' => 1,
            ],
            [
                'combo_id' => $combo2->id,
                'product_id' => $up7->id,
                'quantity' => 1,
            ],
        ]);
    }
}
