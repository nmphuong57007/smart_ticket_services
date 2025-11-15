<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
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

        // ✅ Tạo phim mẫu theo cấu hình
        $count = (int) config('seeder.movies', 100) * (int) config('seeder.multiplier', 1);
        $movies = Movie::factory($count)->create();

        $pivot = [];

        foreach ($movies as $movie) {
            $randomGenres = collect($genreIds)->random(rand(1, 3))->toArray();
            foreach ($randomGenres as $g) {
                $pivot[] = [
                    'movie_id' => $movie->id,
                    'genre_id' => $g,
                ];
            }
        }

        // Chia nhỏ insert để tránh lỗi max packet
        foreach (array_chunk($pivot, 1000) as $chunk) {
            DB::table('movie_genre')->insert($chunk);
        }

        $this->command->info("✅ Đã tạo {$count} phim và gán thể loại ngẫu nhiên bằng bulk-insert.");
    }
}
