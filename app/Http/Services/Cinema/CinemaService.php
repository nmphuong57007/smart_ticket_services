<?php

namespace App\Http\Services\Cinema;

use App\Models\Cinema;
use App\Models\Showtime;
use App\Models\Room;
use Illuminate\Support\Facades\DB;

class CinemaService
{
    /**
     *  Lấy danh sách rạp (phân trang + lọc + sắp xếp)
     */
    public function getCinemas(array $filters)
    {
        return Cinema::withCount('rooms')
            // Lọc theo tên hoặc địa chỉ
            ->when($filters['name'] ?? null, function ($q, $name) {
                $q->where(function ($query) use ($name) {
                    $query->where('name', 'like', "%{$name}%")
                        ->orWhere('address', 'like', "%{$name}%");
                });
            })
            // Lọc theo trạng thái
            ->when(
                $filters['status'] ?? null,
                fn($q, $status) =>
                $q->where('status', $status)
            )
            // Sắp xếp linh hoạt
            ->orderBy($filters['sort_by'] ?? 'id', $filters['sort_order'] ?? 'asc')
            ->paginate($filters['per_page'] ?? 10);
    }

    /**
     *  Lấy chi tiết 1 rạp (kèm danh sách phòng)
     */
    public function getCinemaById(int $id)
    {
        return Cinema::with('rooms:id,cinema_id,name,status')->find($id);
    }

    /**
     *  Thêm mới rạp chiếu
     */
    public function createCinema(array $data): Cinema
    {
        return DB::transaction(fn() => Cinema::create($data));
    }

    /**
     *  Cập nhật thông tin rạp chiếu
     */
    public function updateCinema(Cinema $cinema, array $data): Cinema
    {
        return DB::transaction(function () use ($cinema, $data) {
            $cinema->update($data);
            return $cinema->fresh('rooms');
        });
    }

    /**
     *  Xóa rạp chiếu
     */
    public function deleteCinema(Cinema $cinema): bool
    {
        return DB::transaction(fn() => $cinema->delete());
    }

    /**
     *  Lấy danh sách phòng thuộc rạp
     */
    public function getRoomsByCinema(int $cinemaId)
    {
        $cinema = Cinema::with('rooms:id,cinema_id,name,status')->find($cinemaId);
        return $cinema ? $cinema->rooms : collect(); // trả về Collection thay vì []
    }

    /**
     *  Lấy toàn bộ lịch chiếu của 1 rạp (gộp các phòng)
     */
    public function getShowtimesByCinema(int $cinemaId)
    {
        return Showtime::with(['movie:id,title', 'room:id,name,cinema_id'])
            ->whereHas('room', fn($q) => $q->where('cinema_id', $cinemaId))
            ->orderBy('show_date')
            ->orderBy('show_time')
            ->get();
    }

    /**
     *  Thống kê tổng quan rạp
     */
    public function getCinemaStatistics(): array
    {
        $totalCinemas    = Cinema::count();
        $activeCinemas   = Cinema::where('status', 'active')->count();
        $inactiveCinemas = Cinema::where('status', 'inactive')->count();
        $totalRooms      = Room::count();

        // Thống kê số lượng phòng theo từng rạp
        $roomsByCinema = Room::select('cinema_id', DB::raw('COUNT(*) as total'))
            ->groupBy('cinema_id')
            ->pluck('total', 'cinema_id')
            ->toArray();

        return [
            'total_cinemas'    => $totalCinemas,
            'active_cinemas'   => $activeCinemas,
            'inactive_cinemas' => $inactiveCinemas,
            'total_rooms'      => $totalRooms,
            'rooms_by_cinema'  => $roomsByCinema,
        ];
    }
}
