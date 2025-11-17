<?php

namespace App\Http\Services\Showtime;

use App\Models\Room;
use App\Models\Showtime;
use App\Models\Movie;
use Carbon\Carbon;

class ShowtimeService
{
    /**
     * Lấy danh sách lịch chiếu + lọc + sort + paginate
     */
    public function getShowtimes(array $filters = [])
    {
        return Showtime::with([
            'movie:id,title,poster,release_date,duration',
            'room:id,name,cinema_id',
            'cinema:id,name'
        ])
            ->when($filters['cinema_id'] ?? null, fn($q, $v) => $q->where('cinema_id', $v))
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
     * Hàm chỉ kiểm tra trùng (true/false)
     */
    public function checkOverlap(array $data, $excludeId = null): bool
    {
        return (bool) $this->checkOverlapDetail($data, $excludeId);
    }


    /**
     *  trả về chi tiết suất bị trùng
     */
    public function checkOverlapDetail(array $data, $excludeId = null)
    {
        $buffer = 10;

        // Lấy phim mới để tính thời gian
        $movie = Movie::findOrFail($data['movie_id']);
        $duration = $movie->duration ?? 120;

        // Thời gian mới
        $newStart = Carbon::parse("{$data['show_date']} {$data['show_time']}");
        $newEnd   = (clone $newStart)->addMinutes($duration + $buffer);

        /**
         * Giới hạn giờ mặc định trong ngày (08:00 - 24:00)
         */
        $openTime  = Carbon::parse("{$data['show_date']} 08:00");
        $closeTime = Carbon::parse("{$data['show_date']} 24:00");

        // Suất mới phải bắt đầu sau khi mở cửa
        if ($newStart->lt($openTime)) {
            return [
                "error" => "Suất chiếu phải bắt đầu sau 08:00",
                "limit_start" => "08:00"
            ];
        }

        // Suất mới phải kết thúc trước khi rạp đóng cửa
        if ($newEnd->gt($closeTime)) {
            return [
                "error" => "Suất chiếu phải kết thúc trước 24:00",
                "limit_end" => "24:00"
            ];
        }

        // Lấy suất chiếu trong ngày của phòng
        $existing = Showtime::with(['movie:id,title,duration', 'room.cinema'])
            ->where('room_id', $data['room_id'])
            ->where('show_date', $data['show_date'])
            ->when($excludeId, fn($q) => $q->where('id', '<>', $excludeId))
            ->get();

        foreach ($existing as $item) {
            $existingStart = Carbon::parse("{$item->show_date} {$item->show_time}");
            $existingEnd   = (clone $existingStart)->addMinutes($item->movie->duration + $buffer);

            // Kiểm tra trùng theo buffer
            $isOverlap =
                $existingStart->copy()->subMinutes($buffer)->lt($newEnd) &&
                $existingEnd->copy()->addMinutes($buffer)->gt($newStart);

            if ($isOverlap) {
                return [
                    "existing_showtime_id" => $item->id,
                    "room_id"              => $item->room_id,
                    "room_name"            => $item->room->name,
                    "cinema_name"          => $item->room->cinema->name ?? null,
                    "existing_movie"       => $item->movie->title,
                    "existing_start"       => $existingStart->format("Y-m-d H:i"),
                    "existing_end"         => $existingEnd->format("Y-m-d H:i"),
                    "buffer_minutes"       => $buffer
                ];
            }
        }

        return null; // Không trùng
    }



    /**
     * Tạo lịch chiếu mới
     */
    public function createShowtime(array $data)
    {
        // Lấy chi tiết suất trùng
        $conflict = $this->checkOverlapDetail($data);

        if ($conflict) {
            throw new \Exception(json_encode([
                "message"  => "Lịch chiếu trùng thời gian trong phòng này!",
                "conflict" => $conflict
            ]));
        }

        // Auto lấy cinema_id từ room
        $data['cinema_id'] = Room::find($data['room_id'])->cinema_id ?? null;

        return Showtime::create($data);
    }


    /**
     * Cập nhật lịch chiếu
     */
    public function updateShowtime(int $id, array $data)
    {
        $showtime = Showtime::findOrFail($id);

        // Kiểm tra trùng (trừ chính nó)
        $conflict = $this->checkOverlapDetail($data, $id);

        if ($conflict) {
            throw new \Exception(json_encode([
                "message"  => "Lịch chiếu trùng thời gian trong phòng này!",
                "conflict" => $conflict
            ]));
        }

        // Auto update cinema_id
        if (isset($data['room_id'])) {
            $data['cinema_id'] = Room::find($data['room_id'])->cinema_id ?? null;
        }

        $showtime->update($data);

        return $showtime;
    }


    /**
     * Xóa lịch chiếu
     */
    public function deleteShowtime(int $id)
    {
        $showtime = Showtime::findOrFail($id);
        $showtime->delete();
        return true;
    }


    /**
     * Danh sách ngày chiếu theo phòng
     */
    public function getShowDatesByRoom(int $roomId): array
    {
        return Showtime::where('room_id', $roomId)
            ->select('show_date')
            ->distinct()
            ->orderBy('show_date', 'asc')
            ->pluck('show_date')
            ->toArray();
    }


    /**
     * Danh sách phòng có suất chiếu
     */
    public function getRoomsWithShowtimes(): array
    {
        return Showtime::with('room:id,name,cinema_id')
            ->select('room_id')
            ->distinct()
            ->get()
            ->map(fn($item) => $item->room)
            ->filter()
            ->values()
            ->toArray();
    }


    /**
     * Thống kê cơ bản
     */
    public function getShowtimeStatistics(): array
    {
        return [
            'total_showtimes' => Showtime::count(),
            'total_movies'    => Showtime::distinct('movie_id')->count('movie_id'),
            'total_rooms'     => Showtime::distinct('room_id')->count('room_id'),
        ];
    }
}
