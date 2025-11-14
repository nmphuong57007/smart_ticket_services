<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Movie;
use App\Models\Genre;

class MoviesSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ Tạm tắt kiểm tra khóa ngoại để tránh lỗi truncate
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Xóa dữ liệu các bảng liên kết trước
        DB::table('movie_genre')->truncate();
        DB::table('showtimes')->truncate();
        DB::table('reviews')->truncate();

        // Sau đó mới được xóa bảng movies
        Movie::truncate();

        // ✅ Bật lại kiểm tra khóa ngoại
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Nếu chưa có thể loại thì gọi seed trước
        if (Genre::count() === 0) {
            $this->call(GenresSeeder::class);
        }

        // Lấy danh sách id thể loại
        $genreIds = Genre::pluck('id')->toArray();

        // ✅ Tạo 20 phim mẫu, gán ngẫu nhiên 1-3 thể loại
        Movie::factory(1000)->create()->each(function ($movie) use ($genreIds) {
            $randomGenres = collect($genreIds)->random(rand(1, 3))->toArray();
            $movie->genres()->sync($randomGenres);
        });

        $this->command->info("✅ Đã tạo 1000 phim và gán thể loại ngẫu nhiên thành công.");
    }
}
