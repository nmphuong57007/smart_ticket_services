<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Genre;

class GenresSeeder extends Seeder
{
    public function run(): void
    {
        // Xóa dữ liệu cũ
        Genre::truncate();

        // Danh sách thể loại mẫu
        $genres = [
            'Hành động',
            'Khoa học viễn tưởng',
            'Hài',
            'Kinh dị',
            'Lãng mạn',
            'Phiêu lưu',
            'Hoạt hình',
            'Chiến tranh',
            'Tội phạm',
            'Chính kịch',
            'Thần thoại',
            'Tâm lý',
            'Gia đình',
            'Âm nhạc',
            'Giả tưởng',
        ];

        foreach ($genres as $name) {
            Genre::create([
                'name' => $name,
                'is_active' => true,
            ]);
        }

        echo "Đã seed " . count($genres) . " thể loại phim.\n";
    }
}
