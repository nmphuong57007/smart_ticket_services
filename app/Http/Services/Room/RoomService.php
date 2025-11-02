<?php

namespace App\Http\Services\Room;

use App\Models\Room;
use App\Models\Seat;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Throwable;

class RoomService
{
    // Lấy danh sách phòng (lọc, tìm kiếm, phân trang)
    public function getRooms(array $filters = []): LengthAwarePaginator
    {
        $sortBy = $filters['sort_by'] ?? 'id';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $allowedSorts = ['id', 'name', 'status', 'cinema_id', 'total_seats', 'created_at'];

        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'id';
        }

        return Room::query()
            ->with('cinema:id,name')
            ->when($filters['cinema_id'] ?? null, fn($q, $v) => $q->where('cinema_id', $v))
            ->when($filters['search'] ?? null, fn($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->orderBy($sortBy, $sortOrder)
            ->paginate($filters['per_page'] ?? 15);
    }

    // Lấy thông tin chi tiết 1 phòng
    public function getRoomById(int $id): ?Room
    {
        try {
            return Room::with('cinema:id,name')->findOrFail($id);
        } catch (ModelNotFoundException) {
            return null;
        }
    }

    // Tạo phòng chiếu mới
    public function createRoom(array $data): Room
    {
        return DB::transaction(function () use ($data) {
            $data['seat_map'] = $this->normalizeSeatMap($data['seat_map'] ?? []);
            $data['total_seats'] = $this->computeTotalSeatsFromMap($data['seat_map']);
            return Room::create($data);
        });
    }

    // Cập nhật thông tin phòng chiếu
    public function updateRoom(Room $room, array $data): Room
    {
        return DB::transaction(function () use ($room, $data) {
            if (array_key_exists('seat_map', $data)) {
                $data['seat_map'] = $this->normalizeSeatMap($data['seat_map']);
                $data['total_seats'] = $this->computeTotalSeatsFromMap($data['seat_map']);
            }

            $room->update($data);
            return $room->fresh(['cinema:id,name']);
        });
    }

    // Xóa phòng chiếu
    public function deleteRoom(Room $room): bool
    {
        return DB::transaction(fn() => $room->delete());
    }

    // Lấy danh sách phòng theo rạp
    public function getRoomsByCinema(int $cinemaId): Collection
    {
        return Room::where('cinema_id', $cinemaId)
            ->with('cinema:id,name')
            ->orderBy('name')
            ->get();
    }

    // Thống kê phòng chiếu
    public function getStatistics(): array
    {
        return [
            'total_rooms' => Room::count(),
            'active_rooms' => Room::where('status', 'active')->count(),
            'maintenance_rooms' => Room::where('status', 'maintenance')->count(),
            'closed_rooms' => Room::where('status', 'closed')->count(),
            'total_seats' => (int) Room::sum('total_seats'),
            'average_seats_per_room' => round(Room::avg('total_seats'), 2),
        ];
    }

    // Chuẩn hóa dữ liệu seat_map về mảng an toàn
    private function normalizeSeatMap(mixed $map): array
    {
        if (is_string($map)) {
            $decoded = json_decode($map, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($map) ? $map : [];
    }

    // Tính tổng số ghế từ seat_map
    private function computeTotalSeatsFromMap(array $map): int
    {
        $total = 0;
        foreach ($map as $row) {
            if (!is_array($row)) continue;
            foreach ($row as $seat) {
                if (is_array($seat) && !empty($seat['code'])) {
                    $total++;
                }
            }
        }
        return $total;
    }

    // Tạo ghế cho lịch chiếu dựa trên seat_map của phòng
    public function createSeatsForShowtime($showtime, ?float $basePrice = null): void
    {
        try {
            $room = $showtime->room()->first();
            if (!$room) return;

            $seatMap = $this->normalizeSeatMap($room->seat_map);
            if (empty($seatMap)) return;

            foreach ($seatMap as $row) {
                foreach ($row as $seat) {
                    Seat::create([
                        'showtime_id' => $showtime->id,
                        'seat_code' => $seat['code'] ?? null,
                        'type' => $seat['type'] ?? 'normal',
                        'status' => 'available',
                        'price' => $seat['price'] ?? $basePrice ?? $showtime->price ?? 0,
                    ]);
                }
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
