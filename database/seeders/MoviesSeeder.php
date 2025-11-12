<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Movie;
use App\Models\Genre;

class MoviesSeeder extends Seeder
{
    public function run(): void
    {
        // Xóa dữ liệu cũ
        Movie::truncate();

        // Nếu chưa có thể loại thì seed trước
        if (Genre::count() === 0) {
            $this->call(GenresSeeder::class);
        }

        // Lấy danh sách ID thể loại
        $genreIds = Genre::pluck('id')->toArray();

        // Tạo 20 phim và gán thể loại ngẫu nhiên
        Movie::factory(20)->create()->each(function ($movie) use ($genreIds) {
            $randomGenres = collect($genreIds)->random(rand(1, 3))->toArray();
            $movie->genres()->sync($randomGenres);
        });

        echo "Đã tạo 20 phim và gán thể loại ngẫu nhiên.\n";
    }
}
