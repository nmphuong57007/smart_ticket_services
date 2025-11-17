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
        'cinema_id',
        'show_date',
        'show_time',
        'price',
        'format',
        'language_type',
    ];

    /**
     * Quan hệ: Lịch chiếu -> Phim
     */
    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }

    /**
     * Quan hệ: Lịch chiếu -> Phòng chiếu
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Quan hệ: Lịch chiếu -> Rạp chiếu
     */
    public function cinema()
    {
        return $this->belongsTo(Cinema::class);
    }

    /**
     * Lấy danh sách ghế của phòng (không phải ghế theo suất chiếu)
     * Dùng khi hiển thị sơ đồ ghế phòng.
     */
    public function getRoomSeatsAttribute()
    {
        return $this->room?->seats ?? collect();
    }
}
