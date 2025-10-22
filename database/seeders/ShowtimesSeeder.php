<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Showtime;
use App\Models\Room;
use App\Models\Movie;
use Faker\Factory as Faker;

class ShowtimesSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('vi_VN');

        // Các khung giờ chiếu phổ biến trong rạp
        $showTimes = ['08:00', '10:30', '13:00', '15:30', '18:00', '20:30', '22:45'];

        // Các định dạng và ngôn ngữ
        $formats = ['2D', '3D', 'IMAX', '4DX'];
        $languages = ['sub', 'dub', 'narrated'];

        $rooms = Room::all();
        $movies = Movie::all();

        // Xoá dữ liệu cũ để seed lại
        Showtime::query()->delete();


        foreach ($rooms as $room) {
            // Mỗi phòng chiếu trong 7 ngày tới
            for ($i = 0; $i < 7; $i++) {
                $showDate = now()->addDays($i)->format('Y-m-d');

                // Mỗi ngày, phòng chiếu ngẫu nhiên 2 phim khác nhau
                $dailyMovies = $movies->random(2);

                foreach ($dailyMovies as $movie) {
                    // Mỗi phim có 3–5 suất chiếu khác nhau trong ngày
                    $numShowtimes = rand(3, 5);
                    $selectedTimes = $faker->randomElements($showTimes, $numShowtimes);

                    foreach ($selectedTimes as $time) {
                        Showtime::create([
                            'movie_id' => $movie->id,
                            'room_id' => $room->id,
                            'show_date' => $showDate,
                            'show_time' => $time,
                            'price' => $faker->randomElement([65000, 75000, 85000, 90000, 100000, 120000]),
                            'format' => $faker->randomElement($formats),
                            'language_type' => $faker->randomElement($languages),
                        ]);
                    }
                }
            }
        }

        echo "Seed dữ liệu lịch chiếu (Showtimes) thành công!\n";
    }
}
