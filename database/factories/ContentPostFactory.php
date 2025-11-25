<?php

namespace Database\Factories;

use App\Models\ContentPost;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ContentPostFactory extends Factory
{
    protected $model = ContentPost::class;

    public function definition()
    {
        $faker = fake('vi_VN');

        // Lấy type từ state(), nếu không có → random
        $type = $this->attributes['type']
            ?? fake()->randomElement(['banner', 'news', 'promotion']);

        // Title theo type
        $titles = [
            'banner' => [
                "Khai trương rạp SmartTicket",
                "Giảm giá mùa lễ hội",
                "Ưu đãi cực lớn trong tháng này",
                "Chào mừng bạn đến SmartTicket!",
            ],
            'news' => [
                "Phim mới ra mắt tuần này",
                "Top 5 phim hot nhất phòng vé",
                "Tin nóng: Bom tấn sắp đổ bộ SmartTicket",
                "Hậu trường làm phim mới cực thú vị",
            ],
            'promotion' => [
                "Black Friday giảm giá 70%",
                "Mua 1 tặng 1 vé xem phim",
                "Ưu đãi siêu hot cho thành viên mới",
                "Voucher giảm 50k cho mọi suất chiếu",
            ],
        ];

        // Ảnh theo type
        $images = [
            'banner' => [
                'https://placehold.co/1200x450?text=SmartTicket+Banner',
                'https://placehold.co/1200x450?text=Sale+Big+Event',
                'https://placehold.co/1200x450?text=Welcome+To+Cinema',
            ],
            'news' => [
                'https://placehold.co/600x400?text=Movie+News',
                'https://placehold.co/600x400?text=Hot+Update',
                'https://placehold.co/600x400?text=Cinema+News',
            ],
            'promotion' => [
                'https://placehold.co/600x400?text=Promotion',
                'https://placehold.co/600x400?text=Discount+70%',
                'https://placehold.co/600x400?text=Sale+Movie+Tickets',
            ],
        ];

        // Random title + image
        $title = $faker->randomElement($titles[$type]);
        $image = $faker->randomElement($images[$type]);

        // Thời gian thực tế đẹp
        $created = now()->subDays(rand(1, 30))->setTime(rand(8, 20), rand(0, 59));
        $updated = $created->copy()->addHours(rand(1, 24));
        $published = $created->copy()->addHours(rand(0, 6));

        return [
            'type' => $type,
            'title' => $title,
            'short_description' => $faker->sentence(12),
            'description' => $faker->paragraph(8),

            'slug' => Str::slug($title) . '-' . fake()->unique()->numberBetween(1000, 9999),

            'image' => $image,

            'is_published' => true,
            'published_at' => $published,

            'created_by' => 1,
            'created_by_name' => 'System Administrator',

            'created_at' => $created,
            'updated_at' => $updated,
        ];
    }

    // STATES

    public function banner()
    {
        return $this->state(fn() => ['type' => 'banner']);
    }

    public function news()
    {
        return $this->state(fn() => ['type' => 'news']);
    }

    public function promotion()
    {
        return $this->state(fn() => ['type' => 'promotion']);
    }
}
