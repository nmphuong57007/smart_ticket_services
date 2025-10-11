<?php

namespace Database\Factories;

use App\Models\Movie;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MovieFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Movie::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $genres = ['Hành động', 'Khoa học viễn tưởng', 'Hài', 'Kinh dị', 'Lãng mạn', 'Phiêu lưu'];
        $formats = ['2D', '3D', 'IMAX', '4DX'];
        $validStatuses = ['coming', 'showing', 'stopped'];

        return [
            // Cột 'title'
            'title' => $this->faker->sentence(3),

            // Cột 'poster' (URL hình ảnh giả)
            'poster' => $this->faker->imageUrl(640, 480, 'movies'),

            // Cột 'trailer' (URL Youtube giả)
            'trailer' => 'https://www.youtube.com/watch?v=' . Str::random(11),

            // Cột 'description' (Đoạn văn dài)
            'description' => $this->faker->paragraph(4),

            // Cột 'genre' (Chọn ngẫu nhiên 2 thể loại và nối lại)
            'genre' => $this->faker->randomElements($genres, 2, false)[0] . ', ' . $this->faker->randomElements($genres, 2, false)[1],

            // Cột 'duration' (Thời lượng ngẫu nhiên từ 90 đến 150 phút)
            'duration' => $this->faker->numberBetween(90, 150),

            // Cột 'format' (Định dạng ngẫu nhiên)
            'format' => $this->faker->randomElement($formats),

            // Cột 'release_date' (Ngày phát hành trong 1 năm qua)
            'release_date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),

            // Cột 'status' (Trạng thái ngẫu nhiên: 1 cho Đang chiếu, 0 cho Sắp chiếu/Đã kết thúc)
            'status' => $this->faker->randomElement($validStatuses), // 80% là true (Đang chiếu)

            // created_at & updated_at được xử lý tự động
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s')
        ];
    }
}
