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

        // default (news)
        $title = $faker->randomElement([
            "Phim mới ra mắt tuần này",
            "Top 5 phim đang dẫn đầu phòng vé",
            "Tin nóng: Bom tấn sắp đổ bộ SmartTicket",
        ]);

        return [
            'type' => 'news',
            'title' => $title,
            'short_description' => $faker->sentence(10),
            'description' => $faker->paragraph(6),
            'slug' => Str::slug($title) . '-' . $faker->unique()->numberBetween(100, 999),
            'image' => 'https://image.tmdb.org/t/p/w780/kqjL17yufvn9OVLyXYpvtyrFfak.jpg',

            'is_published' => true,
            'published_at' => now(),

            'created_by' => 1,
            'created_by_name' => 'System Administrator',
        ];
    }

    /**
     * STATE BANNER
     */
    public function banner()
    {
        return $this->state(function () {
            $title = fake('vi_VN')->randomElement([
                "Khai trương rạp SmartTicket",
                "Giảm giá mùa lễ hội",
                "Ưu đãi lớn trong tháng"
            ]);

            return [
                'type' => 'banner',
                'title' => $title,
                'slug' => Str::slug($title) . '-' . rand(100,999),
                'image' => fake()->randomElement([
                    'https://image.tmdb.org/t/p/original/fiVW06jE7z9YnO4trhaMEdclSiC.jpg',
                    'https://image.tmdb.org/t/p/original/8YFL5QQVPy3AgrEQxNYVSgiPEbe.jpg',
                    'https://image.tmdb.org/t/p/original/7WsyChQLEftFiDOVTGkv3hFpyyt.jpg',
                ]),
            ];
        });
    }

    /**
     * STATE PROMOTION
     */
    public function promotion()
    {
        return $this->state(function () {
            $title = fake('vi_VN')->randomElement([
                "Black Friday giảm giá 70%",
                "Mua 1 tặng 1 vé xem phim",
                "Ưu đãi khủng dành cho thành viên",
            ]);

            return [
                'type' => 'promotion',
                'title' => $title,
                'slug' => Str::slug($title) . '-' . rand(100,999),
                'image' => fake()->randomElement([
                    'https://image.tmdb.org/t/p/w500/1AhR8Vg1mmSRITxHSc3CmK8A9qe.jpg',
                    'https://image.tmdb.org/t/p/w500/fRrpOILyXuWaWLmqF7kXeMVwITQ.jpg',
                    'https://image.tmdb.org/t/p/w500/9gk7adHYeDvHkCSEqAvQNLV5Uge.jpg',
                ]),
            ];
        });
    }
}
