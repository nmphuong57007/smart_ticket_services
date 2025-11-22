<?php

namespace App\Http\Services\Showtime;

use Carbon\Carbon;
use App\Models\Room;
use App\Models\Movie;
use App\Models\Showtime;
use App\Http\Services\Room\RoomService;
use App\Http\Resources\ShowtimeResource;

class ShowtimeService
{

    private function isSeeding(): bool
    {
        if (!app()->runningInConsole()) return false;

        $argv = request()->server('argv');
        if (!is_array($argv) || count($argv) < 2) return false;

        return in_array($argv[1], ['db:seed', 'migrate:fresh', 'migrate:fresh --seed']);
    }


    public function getShowtimes(array $filters = [])
    {
        return Showtime::with([
            'movie:id,title,poster,release_date,duration',
            'room:id,name',
        ])
            ->when($filters['room_id'] ?? null, fn($q, $v) => $q->where('room_id', $v))
            ->when($filters['movie_id'] ?? null, fn($q, $v) => $q->where('movie_id', $v))
            ->when($filters['show_date'] ?? null, fn($q, $v) => $q->where('show_date', $v))
            ->when($filters['from_date'] ?? null, fn($q, $v) => $q->whereDate('show_date', '>=', $v))
            ->when($filters['to_date'] ?? null, fn($q, $v) => $q->whereDate('show_date', '<=', $v))
            ->orderBy($filters['sort_by'] ?? 'show_date', $filters['sort_order'] ?? 'asc')
            ->orderBy('show_time', 'asc')
            ->paginate($filters['per_page'] ?? 10);
    }


    private function calculatePrice(string $date): int
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $weekdayPrice = config('pricing.base_price.weekday');
        $weekendPrice = config('pricing.base_price.weekend');

        return in_array($dayOfWeek, [6, 0]) ? $weekendPrice : $weekdayPrice;
    }


    public function checkOverlapDetail(array $data, $excludeId = null)
    {
        if ($this->isSeeding()) {
            return null;
        }

        $buffer = 10; // phút nghỉ giữa hai suất chiếu

        $movie = Movie::findOrFail($data['movie_id']);
        $duration = $movie->duration ?? 120;

        $newStart = Carbon::parse("{$data['show_date']} {$data['show_time']}");

        // Giờ mở cửa / đóng cửa
        $openTime  = Carbon::parse("{$data['show_date']} 08:00");
        $closeTime = Carbon::parse("{$data['show_date']} 24:00");

        if ($newStart->lt($openTime)) {
            return ["error" => "Suất chiếu phải bắt đầu sau 08:00"];
        }

        // Lấy các suất chiếu khác trong ngày của phòng
        $existing = Showtime::with(['movie:id,title,duration'])
            ->where('room_id', $data['room_id'])
            ->where('show_date', $data['show_date'])
            ->when($excludeId, fn($q) => $q->where('id', '<>', $excludeId))
            ->orderBy('show_time')
            ->get();

        foreach ($existing as $item) {

            // Suất chiếu hiện tại
            $existingStart = Carbon::parse("{$item->show_date} {$item->show_time}");
            $existingEnd   = (clone $existingStart)->addMinutes($item->movie->duration);

            // Thời gian sớm nhất được phép bắt đầu suất mới
            $requiredNextStart = (clone $existingEnd)->addMinutes($buffer);

            // Nếu newStart < required → trùng
            if ($newStart->lt($requiredNextStart)) {
                return [
                    "existing_showtime_id" => $item->id,
                    "room_id"              => $item->room_id,
                    "existing_movie"       => $item->movie->title,
                    "existing_start"       => $existingStart->format("Y-m-d H:i"),
                    "existing_end"         => $existingEnd->format("Y-m-d H:i"),
                    "required_next_start"  => $requiredNextStart->format("Y-m-d H:i"),
                ];
            }
        }

        return null;
    }


    /**
     * CREATE SHOWTIME – ĐÃ THÊM FULL LOGIC RÀNG BUỘC PHIM
     */
    public function createShowtime(array $data)
    {
        $movie = Movie::findOrFail($data['movie_id']);
        $isSeeding = $this->isSeeding();

        if (!$isSeeding) {
            $today = Carbon::today()->format('Y-m-d');
            $nowTime = Carbon::now()->format('H:i');

            if ($data['show_date'] < $today) {
                throw new \Exception("Không thể tạo suất chiếu trong quá khứ.");
            }

            if ($data['show_date'] === $today && $data['show_time'] < $nowTime) {
                throw new \Exception("Giờ chiếu đã qua — không thể tạo suất chiếu.");
            }
        }

        if (!$isSeeding && $movie->status === 'stopped') {
            throw new \Exception("Phim đã ngừng chiếu – không thể tạo suất chiếu.");
        }

        if (!$isSeeding && $movie->status === 'coming') {
            if ($movie->release_date && $data['show_date'] < $movie->release_date) {
                throw new \Exception("Phim chưa đến ngày khởi chiếu – không thể tạo suất chiếu.");
            }
        }

        if ($movie->status === 'coming') {
            $movie->update(['status' => 'showing']);
        }

        if (!$isSeeding) {
            $conflict = $this->checkOverlapDetail($data);
            if ($conflict) {
                throw new \Exception(json_encode([
                    "message"  => "Lịch chiếu trùng thời gian trong phòng này!",
                    "conflict" => $conflict
                ]));
            }
        }

        // Tính giá theo weekday/weekend
        $data['price'] = $this->calculatePrice($data['show_date']);

        // Tạo suất chiếu
        $showtime = Showtime::create($data);

        if (!$isSeeding) {
            app(RoomService::class)
                ->createSeatsForShowtime($showtime);
        }

        return $showtime;
    }


    public function updateShowtime(int $id, array $data)
    {
        $showtime = Showtime::findOrFail($id);

        $checkData = [
            'movie_id'  => $data['movie_id']  ?? $showtime->movie_id,
            'room_id'   => $data['room_id']   ?? $showtime->room_id,
            'show_date' => $data['show_date'] ?? $showtime->show_date,
            'show_time' => $data['show_time'] ?? $showtime->show_time,
        ];

        $conflict = $this->checkOverlapDetail($checkData, $id);
        if ($conflict) {
            throw new \Exception(json_encode([
                "message"  => "Lịch chiếu trùng thời gian trong phòng này!",
                "conflict" => $conflict
            ]));
        }

        if (isset($data['show_date'])) {
            $data['price'] = $this->calculatePrice($data['show_date']);
        }

        $showtime->update($data);

        return $showtime;
    }

    public function deleteShowtime(int $id)
    {
        Showtime::findOrFail($id)->delete();
        return true;
    }

    public function getShowDatesByRoom(int $roomId): array
    {
        return Showtime::where('room_id', $roomId)
            ->select('show_date')
            ->distinct()
            ->orderBy('show_date', 'asc')
            ->pluck('show_date')
            ->toArray();
    }

    public function getRoomsWithShowtimes(): array
    {
        return Showtime::with('room:id,name')
            ->select('room_id')
            ->distinct()
            ->get()
            ->map(fn($item) => $item->room)
            ->filter()
            ->values()
            ->toArray();
    }

    public function getShowtimeStatistics(): array
    {
        return [
            'total_showtimes' => Showtime::count(),
            'total_movies'    => Showtime::distinct('movie_id')->count('movie_id'),
            'total_rooms'     => Showtime::distinct('room_id')->count('room_id'),
        ];
    }

    public function getShowtimeById(int $id): ?Showtime
    {
        return Showtime::with(['room', 'seats'])->find($id);
    }

    public function getSeatsByShowtime(int $showtimeId)
    {
        return Showtime::with(['seats'])->findOrFail($showtimeId)->seats;
    }

    public function getStatisticsByDate(string $date): array
    {
        $showtimes = Showtime::with(['movie:id,title,duration,poster', 'room:id,name'])
            ->where('show_date', $date)
            ->orderBy('show_time')
            ->get();

        return [
            'date'               => $date,
            'total_showtimes'    => $showtimes->count(),
            'total_movies'       => $showtimes->pluck('movie_id')->unique()->count(),
            'total_rooms'        => $showtimes->pluck('room_id')->unique()->count(),
            'showtimes'          => ShowtimeResource::collection($showtimes),
        ];
    }
}
