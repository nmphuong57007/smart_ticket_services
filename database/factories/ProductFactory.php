<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        $faker = fake('vi_VN');

        // Khi dùng state(['type' => 'combo']), factory sẽ nhận được type đúng
        $type = $this->faker->randomElement(['combo', 'food', 'drink']);

        /**
         * Nếu state có type → ưu tiên type từ state
         */
        if (isset($this->states[0])) {
            $stateArray = $this->states[0]->__invoke([]);
            if (isset($stateArray['type'])) {
                $type = $stateArray['type'];
            }
        }

        $images = [
            'combo' => [
                'https://placehold.co/600x400?text=Combo+1',
                'https://placehold.co/600x400?text=Combo+2',
                'https://placehold.co/600x400?text=Combo+3',
                'https://placehold.co/600x400?text=Combo+4',
            ],
            'food' => [
                'https://placehold.co/600x400?text=Popcorn',
                'https://placehold.co/600x400?text=Snack',
                'https://placehold.co/600x400?text=Nacho',
            ],
            'drink' => [
                'https://placehold.co/600x400?text=Coke',
                'https://placehold.co/600x400?text=Pepsi',
                'https://placehold.co/600x400?text=Iced+Tea',
            ],
        ];

        $names = [
            'combo' => [
                'Combo Siêu Tiết Kiệm',
                'Combo Bắp Nước 1 Người',
                'Combo Gia Đình',
                'Combo Cảm Giác Mạnh',
                'Combo Nhóm Bạn 4 Người',
            ],
            'food' => [
                'Bắp Rang Bơ Truyền Thống',
                'Bắp Caramel',
                'Khoai Tây Chiên',
                'Xúc Xích Đức',
                'Nacho Phô Mai',
            ],
            'drink' => [
                'Coca Cola 330ml',
                'Pepsi 330ml',
                'Trà Chanh Tươi',
                'Fanta Cam',
                'Nước Suối Aquafina',
            ],
        ];

        return [
            'name' => $faker->randomElement($names[$type]),
            'price' => $faker->numberBetween(20, 120) * 1000,
            'description' => $faker->sentence(12),
            'image' => $faker->randomElement($images[$type]),
            'stock' => $faker->numberBetween(0, 100),
            'type' => $type,
            'is_active' => true,
            'created_at' => now()->subDays(rand(1, 20)),
            'updated_at' => now(),
        ];
    }

    public function combo()
    {
        return $this->state(fn() => ['type' => 'combo']);
    }

    public function food()
    {
        return $this->state(fn() => ['type' => 'food']);
    }

    public function drink()
    {
        return $this->state(fn() => ['type' => 'drink']);
    }
}
