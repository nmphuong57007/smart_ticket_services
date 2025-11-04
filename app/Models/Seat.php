<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'room_id',
        'seat_code',
        'type',
        'status',
        'price'
    ];

    // Quan hệ ngược tới showtime
    public function showtime()
    {
        return $this->belongsTo(Showtime::class);
    }
}
