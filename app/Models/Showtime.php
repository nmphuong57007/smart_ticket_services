<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Showtime extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'movie_id',
        'room_id',
        'show_date',
        'show_time',
        'price',
        'format',
        'language_type',
    ];

    // Lấy thông tin phim
    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }

    // Lấy thông tin phòng chiếu
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    // Lấy ghế thông qua phòng chiếu
    public function seats()
    {
        return $this->hasManyThrough(
            Seat::class,   // Model cuối cùng
            Room::class,   // Model trung gian
            'id',          // khóa chính ở Room (Room.id)
            'room_id',     // khóa ngoại ở Seat (Seat.room_id)
            'room_id',     // khóa ngoại ở Showtime (Showtime.room_id)
            'id'           // khóa chính ở Room (Room.id)
        );
    }
}
