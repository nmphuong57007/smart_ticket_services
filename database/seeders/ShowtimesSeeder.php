<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\Movie;
use App\Http\Services\Showtime\ShowtimeService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShowtimesSeeder extends Seeder
{
    public function run(): void
    {
        $rooms  = Room::all();
        $movies = Movie::all();

        if ($rooms->isEmpty() || $movies->isEmpty()) {
            echo "Rooms hoặc Movies đang rỗng.\n";
            return;
        }

        // XÓA nhưng không TRUNCATE tránh lỗi FK
        DB::table('showtimes')->delete();

        $formats   = ['2D', '3D'];
        $languages = ['sub', 'dub', 'narrated'];

        $today = now()->format('Y-m-d');
        $weekday = now()->dayOfWeekIso; // 1–7

        // base price từ config
        $priceConfig = config('pricing.base_price');
        $basePrice = $weekday >= 6 ? $priceConfig['weekend'] : $priceConfig['weekday'];

        // Danh sách giờ cố định
        $slots = [
            '08:00',
            '10:30',
            '13:00',
            '15:30',
            '18:00',
            '20:30'
        ];

        $service = app(ShowtimeService::class);
        $count = 0;

        $roomIndex = 0;
        $slotIndex = 0;

        foreach ($movies as $movie) {

            // Chọn phòng theo vòng lặp
            $room = $rooms[$roomIndex % $rooms->count()];
            $roomIndex++;

            // Chọn slot theo vòng lặp
            $slot = $slots[$slotIndex % count($slots)];
            $slotIndex++;

            $data = [
                'movie_id'      => $movie->id,
                'room_id'       => $room->id,
                'show_date'     => $today,
                'show_time'     => $slot,
                'format'        => $formats[array_rand($formats)],
                'language_type' => $languages[array_rand($languages)],
                'price'         => $basePrice,
            ];

            try {
                $service->createShowtime($data);
                $count++;
            } catch (\Exception $e) {
                echo "Lỗi tạo suất cho phim {$movie->title}: {$e->getMessage()}\n";
            }
        }

        echo "ĐÃ TẠO ĐỦ $count SUẤT CHIẾU (100 PHIM = 100 SUẤT) — KHÔNG BỎ SÓT\n";
    }
}
