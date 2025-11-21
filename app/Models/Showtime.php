<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Showtime extends Model
{
    use HasFactory;

    protected $fillable = [
        'movie_id',
        'room_id',
        'show_date',
        'show_time',
        'price',
        'format',
        'language_type',
    ];

    /**
     * Quan hệ: Suất chiếu -> Phim
     */
    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }

    /**
     * Quan hệ: Suất chiếu -> Phòng chiếu
     * (phòng thuộc rạp mặc định ID = 1)
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Quan hệ: Suất chiếu -> Ghế theo suất chiếu
     */
    public function seats()
    {
        return $this->hasMany(Seat::class);
    }

    /**
     * Lấy seat_map của phòng để frontend hiển thị sơ đồ ghế
     * GHẾ THẬT vẫn thuộc showtime (không thuộc room)
     */
    public function getSeatMapAttribute()
    {
        return $this->room?->seat_map ?? [];
    }
}
