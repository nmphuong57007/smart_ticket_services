<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'cinema_id',
        'name',
        'seat_map',
        'total_seats',
        'status',
    ];

    protected $casts = [
        'seat_map'     => 'array',
        'total_seats'  => 'integer',
    ];

    protected $attributes = [
        'status' => 'active',
    ];

    /**
     * Hook saving: tự động tính total_seats mỗi khi lưu
     */
    protected static function booted()
    {
        static::saving(function ($room) {
            if (!empty($room->seat_map)) {
                $room->total_seats = $room->computeTotalSeats($room->seat_map);
            } else {
                $room->total_seats = 0;
            }
        });
    }

    /**
     * Quan hệ: Room -> Cinema (1 rạp duy nhất)
     */
    public function cinema()
    {
        return $this->belongsTo(Cinema::class);
    }

    /**
     * Quan hệ: Room -> Showtime
     * (Ghế nằm ở Showtime, không nằm ở Room)
     */
    public function showtimes()
    {
        return $this->hasMany(Showtime::class);
    }

    /**
 feat/promotions_post
     * Quan hệ với seats thông qua showtimes
     */

    public function seats(): HasManyThrough
    {
        return $this->hasManyThrough(
            Seat::class,
            Showtime::class,
            'room_id',      // khóa ngoại showtimes
            'showtime_id',  // khóa ngoại seats
            'id',           // khóa chính rooms
            'id'            // khóa chính showtimes
        );

    }

   
    public function setSeatMapAttribute($value): void
    {
        $map = is_array($value) ? $value : json_decode($value, true);
        $this->attributes['seat_map'] = json_encode($map ?? []);
    }

    /**
     * Tính tổng ghế trong seat_map
     */
    public function computeTotalSeats(array $seatMap): int
    {
        $count = 0;

        foreach ($seatMap as $row) {
            if (!is_array($row)) continue;

            foreach ($row as $seat) {
                // Case 1: ghế dạng string: "A1"
                if (is_string($seat)) {
                    $count++;
                }
                // Case 2: ghế dạng object: ["code" => "A1", "type" => "vip"]
                elseif (is_array($seat) && !empty($seat['code'])) {
                    $count++;
                }
            }
        }

        return $count;
    }
}
