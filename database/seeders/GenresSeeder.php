<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Genre;

class GenresSeeder extends Seeder
{
    public function run(): void
    {
        // Tạm tắt kiểm tra khóa ngoại để tránh lỗi truncate
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Xóa dữ liệu bảng pivot trước (movie_genre có FK tới genres)
        DB::table('movie_genre')->truncate();

        // Xóa bảng genres
        Genre::truncate();

        // Bật lại kiểm tra khóa ngoại
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

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

        // Thêm dữ liệu
        foreach ($genres as $name) {
            Genre::create([
                'name' => $name,
                'is_active' => true,
            ]);
        }

        $this->command->info('✅ Đã seed ' . count($genres) . ' thể loại phim thành công.');
    }
}
