<?php

namespace App\Http\Services\Room;

use App\Models\Room;
use App\Models\Seat;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

class RoomService
{
    /**
     * Danh sách phòng chiếu
     */
    public function getRooms(array $filters = []): LengthAwarePaginator
    {
        $sortBy = $filters['sort_by'] ?? 'id';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        $allowedSorts = ['id', 'name', 'status', 'total_seats', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        return Room::query()
            ->when($filters['search'] ?? null, fn($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->orderBy($sortBy, $sortOrder)
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Lấy chi tiết phòng
     */
    public function getRoomById(int $id): ?Room
    {
        return Room::find($id);
    }

    /**
     * Tạo phòng chiếu
     */
    public function createRoom(array $data): Room
    {
        return DB::transaction(function () use ($data) {

            $data['cinema_id'] = 1;

            if (isset($data['seat_map'])) {
                $data['seat_map'] = $this->normalizeSeatMap($data['seat_map']);
            }

            return Room::create($data);
        });
    }

    /**
     * Cập nhật phòng
     */
    public function updateRoom(Room $room, array $data): Room
    {
        return DB::transaction(function () use ($room, $data) {

            if ($room->showtimes()->exists() && isset($data['seat_map'])) {
                throw new \Exception("Phòng đã có suất chiếu — không được phép sửa seat_map.");
            }

            if (isset($data['seat_map']) && !$room->showtimes()->exists()) {
                $data['seat_map'] = $this->normalizeSeatMap($data['seat_map']);
            }

            unset($data['cinema_id']);

            $room->update($data);
            return $room;
        });
    }

    /**
     * Xóa phòng
     */
    public function deleteRoom(Room $room): bool
    {
        if ($room->showtimes()->exists()) {
            throw new \Exception("Phòng đã có suất chiếu — không thể xóa.");
        }

        return $room->delete();
    }

    /**
     * Chuẩn hóa seat_map (string → array)
     */
    private function normalizeSeatMap(mixed $map): array
    {
        if (is_string($map)) {
            $decoded = json_decode($map, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($map) ? $map : [];
    }

    /**
     * Tạo seats theo suất chiếu
     * Xử lý trạng thái ghế vật lý:
     * active  → available
     * broken  → unavailable
     * blocked → unavailable
     */
    public function createSeatsForShowtime($showtime, ?float $basePrice = null): void
    {
        try {
            $room = $showtime->room()->first();
            if (!$room) return;

            $seatMap = $this->normalizeSeatMap($room->seat_map);
            if (empty($seatMap)) return;

            foreach ($seatMap as $row) {
                foreach ($row as $seat) {

                    // Nếu chỉ là string "A1" → convert sang object
                    if (is_string($seat)) {
                        $seat = [
                            'code'   => $seat,
                            'type'   => 'normal',
                            'status' => 'active', // mặc định
                        ];
                    }

                    $type = $seat['type'] ?? 'normal';
                    $physicalStatus = $seat['status'] ?? 'active';

                    // Xác định trạng thái ghế trong suất chiếu
                    $seatStatus = ($physicalStatus === 'active')
                        ? 'available'
                        : 'unavailable';

                    // Lấy hệ số multiplier
                    $multiplier = config("pricing.seat_multiplier.$type", 1.0);

                    // Base price = giá suất chiếu
                    $base = $showtime->price;

                    // Tính giá ghế = base × multiplier
                    $finalPrice = $base * $multiplier;

                    // Làm tròn về số chẵn nghìn
                    $finalPrice = round($finalPrice / 1000) * 1000;

                    Seat::create([
                        'showtime_id' => $showtime->id,
                        'seat_code'   => $seat['code'],
                        'type'        => $type,
                        'status'      => $seatStatus,   // GHẾ SUẤT CHIẾU
                        'price'       => $finalPrice,
                    ]);
                }
            }
        } catch (Throwable $e) {
            report($e);
        }
    }


    /**
     * Thống kê phòng
     */
    public function getStatistics(): array
    {
        return [
            'total_rooms'       => Room::count(),
            'active_rooms'      => Room::where('status', 'active')->count(),
            'maintenance_rooms' => Room::where('status', 'maintenance')->count(),
            'closed_rooms'      => Room::where('status', 'closed')->count(),
            'total_seats'       => (int) Room::sum('total_seats'),
        ];
    }
}
