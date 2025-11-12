<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Showtime;
use App\Models\Room;
use App\Models\Movie;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ShowtimesSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('vi_VN');

        // Các khung giờ chiếu phổ biến
        $timeSlots = ['08:00', '10:30', '13:00', '15:30', '18:00', '20:30', '22:45'];

        // Các định dạng & ngôn ngữ
        $formats = ['2D', '3D', 'IMAX', '4DX'];
        $languages = ['sub', 'dub', 'narrated'];

        $rooms = Room::all();
        $movies = Movie::all();

        Showtime::query()->delete();

        $showtimeData = [];

        foreach ($rooms as $room) {
            // 7 ngày tới mỗi phòng chiếu vài phim
            for ($i = 0; $i < 7; $i++) {
                $showDate = Carbon::now('Asia/Ho_Chi_Minh')->addDays($i)->format('Y-m-d');
                $dailyMovies = $movies->random(min(2, $movies->count()));

                foreach ($dailyMovies as $movie) {
                    $selectedTimes = $faker->randomElements($timeSlots, rand(3, 5));

                    foreach ($selectedTimes as $time) {
                        $showtimeData[] = [
                            'movie_id'       => $movie->id,
                            'room_id'        => $room->id,
                            'show_date'      => $showDate,
                            'show_time'      => $time,
                            'price'          => $faker->randomElement([65000, 75000, 85000, 90000, 100000, 120000]),
                            'format'         => $faker->randomElement($formats),
                            'language_type'  => $faker->randomElement($languages),
                        ];
                    }
                }
            }
        }

        foreach (array_chunk($showtimeData, 1000) as $chunk) {
            DB::table('showtimes')->insert($chunk);
        }

        echo "✅ Đã seed " . count($showtimeData) . " lịch chiếu thành công!\n";
    }
}
