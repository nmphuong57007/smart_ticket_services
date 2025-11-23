<?php

namespace Database\Seeders;

use App\Models\PromotionPost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PromotionPostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $posts = [
            [
                'title' => 'Ưu đãi tháng 11 - Mua vé liền tay',
                'description' => 'Giảm giá tới 50% cho khách hàng đặt vé online trong tháng 11.',
                'image_url' => '/images/promotions/sale-nov-50.jpg',
                'target_url' => '/booking',
                'published_at' => now(),
                'is_published' => true,
                'created_by' => 1,
            ],
            [
                'title' => 'Combo bắp nước siêu tiết kiệm',
                'description' => 'Giá rẻ bất ngờ với combo bắp nước tại rạp.',
                'image_url' => '/images/promotions/combo-popcorn.jpg',
                'target_url' => '/combo',
                'published_at' => now()->subDay(),
                'is_published' => true,
                'created_by' => 1,
            ],
            [
                'title' => 'Happy day thứ 4 hàng tuần',
                'description' => 'Xem phim thỏa thích giá chỉ 45.000đ!',
                'image_url' => '/images/promotions/happy-wednesday.jpg',
                'target_url' => '/events/happy-wednesday',
                'published_at' => null,
                'is_published' => false,
                'created_by' => 1,
            ],
        ];

        foreach ($posts as $post) {
            $post['slug'] = Str::slug($post['title']);
            PromotionPost::create($post);
        }
    }
}
