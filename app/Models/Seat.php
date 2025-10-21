<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'showtime_id',
        'seat_code',
        'type',
        'status'
    ];

    // Quan hệ ngược tới showtime
    public function showtime()
    {
        return $this->belongsTo(Showtime::class);
    }
}
