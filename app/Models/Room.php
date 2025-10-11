<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'cinema_id',
        'name',
        'seat_map', // có thể lưu JSON map ghế
    ];

    /**
     * Lấy danh sách showtimes của phòng này
     */
    public function showtimes()
    {
        return $this->hasMany(Showtime::class);
    }
}
