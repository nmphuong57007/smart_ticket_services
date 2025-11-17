<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;
use App\Models\Movie;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class ShowtimesSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('vi_VN');

        $showTimes = [
            '08:00:00',
            '10:30:00',
            '13:00:00',
            '15:30:00',
            '18:00:00',
            '20:30:00',
            '22:45:00'
        ];

        $formats   = ['2D', '3D', 'IMAX', '4DX'];
        $languages = ['sub', 'dub', 'narrated'];

        $rooms  = Room::all();
        $movies = Movie::all();

        if ($rooms->isEmpty() || $movies->isEmpty()) {
            echo "Không thể seed: Rooms hoặc Movies đang rỗng.\n";
            return;
        }

        // FIX LỖI TRUNCATE FK
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('showtimes')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $data = [];
        $days = (int) config('seeder.showtime_days', 7);

        foreach ($rooms as $room) {
            for ($i = 0; $i < $days; $i++) {

                $date = now()->addDays($i)->format('Y-m-d');

                $numShowtimes = rand(4, 6);
                $times = $faker->randomElements($showTimes, $numShowtimes);

                foreach ($times as $time) {

                    $movie = $movies->random();

                    $data[] = [
                        'room_id'       => $room->id,
                        'cinema_id'     => $room->cinema_id,
                        'movie_id'      => $movie->id,
                        'show_date'     => $date,
                        'show_time'     => $time,
                        'price'         => $faker->randomElement([65000, 75000, 85000, 90000, 100000, 120000]),
                        'format'        => $faker->randomElement($formats),
                        'language_type' => $faker->randomElement($languages),
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ];
                }
            }
        }

        foreach (array_chunk($data, 1000) as $chunk) {
            DB::table('showtimes')->insert($chunk);
        }

        echo "Seed lịch chiếu thành công! Tổng: " . count($data) . " suất.\n";
    }
}
