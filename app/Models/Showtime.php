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
        return $this->belongsTo(\App\Models\Movie::class);
    }

    // Lấy thông tin phòng chiếu
    public function room()
    {
        return $this->belongsTo(\App\Models\Room::class);
    }
}
