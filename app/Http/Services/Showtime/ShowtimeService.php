<?php

namespace App\Http\Services\Showtime;

use App\Models\Room;
use App\Models\Showtime;
use App\Models\Movie;
use Carbon\Carbon;

class ShowtimeService
{
    /**
     * Kiểm tra đang chạy DB Seeder hay không
     */
    private function isSeeding(): bool
    {
        if (!app()->runningInConsole()) return false;

        $argv = request()->server('argv');
        if (!is_array($argv) || count($argv) < 2) return false;

        return in_array($argv[1], ['db:seed', 'migrate:fresh', 'migrate:fresh --seed']);
    }

    /**
     * Lấy danh sách lịch chiếu
     */
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

    /**
     * TÍNH GIÁ THEO NGÀY
     */
    private function calculatePrice(string $date): int
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $weekdayPrice = config('pricing.base_price.weekday');
        $weekendPrice = config('pricing.base_price.weekend');

        return in_array($dayOfWeek, [6, 0]) ? $weekendPrice : $weekdayPrice;
    }

    /**
     * Kiểm tra trùng suất chiếu – bỏ qua nếu seeding
     */
    public function checkOverlapDetail(array $data, $excludeId = null)
    {
        // 💥 Nếu đang seed → bỏ qua kiểm tra trùng giờ
        if ($this->isSeeding()) {
            return null;
        }

        $buffer = 10;

        $movie = Movie::findOrFail($data['movie_id']);
        $duration = $movie->duration ?? 120;

        $newStart = Carbon::parse("{$data['show_date']} {$data['show_time']}");
        $newEnd   = (clone $newStart)->addMinutes($duration + $buffer);

        $openTime  = Carbon::parse("{$data['show_date']} 08:00");
        $closeTime = Carbon::parse("{$data['show_date']} 24:00");

        if ($newStart->lt($openTime)) {
            return ["error" => "Suất chiếu phải bắt đầu sau 08:00"];
        }

        if ($newEnd->gt($closeTime)) {
            return ["error" => "Suất chiếu phải kết thúc trước 24:00"];
        }

        $existing = Showtime::with(['movie:id,title,duration'])
            ->where('room_id', $data['room_id'])
            ->where('show_date', $data['show_date'])
            ->when($excludeId, fn($q) => $q->where('id', '<>', $excludeId))
            ->get();

        foreach ($existing as $item) {
            $existingStart = Carbon::parse("{$item->show_date} {$item->show_time}");
            $existingEnd   = (clone $existingStart)->addMinutes($item->movie->duration + $buffer);

            $isOverlap =
                $existingStart->copy()->subMinutes($buffer)->lt($newEnd) &&
                $existingEnd->copy()->addMinutes($buffer)->gt($newStart);

            if ($isOverlap) {
                return [
                    "existing_showtime_id" => $item->id,
                    "room_id"              => $item->room_id,
                    "existing_movie"       => $item->movie->title,
                    "existing_start"       => $existingStart->format("Y-m-d H:i"),
                    "existing_end"         => $existingEnd->format("Y-m-d H:i"),
                ];
            }
        }

        return null;
    }

    /**
     * Tạo lịch chiếu mới + auto tạo ghế
     */
    public function createShowtime(array $data)
    {
        // checkOverlapDetail() sẽ tự bỏ qua nếu đang seed
        $conflict = $this->checkOverlapDetail($data);
        if ($conflict) {
            throw new \Exception(json_encode([
                "message"  => "Lịch chiếu trùng thời gian trong phòng này!",
                "conflict" => $conflict
            ]));
        }

        // Tính giá weekday/weekend
        $data['price'] = $this->calculatePrice($data['show_date']);

        $showtime = Showtime::create($data);

        // Tạo ghế theo suất chiếu
        app(\App\Http\Services\Room\RoomService::class)
            ->createSeatsForShowtime($showtime);

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
}
