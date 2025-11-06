<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'room_id',
        'seat_code',
        'type',
        'status',
        'price'
    ];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];


    // Quan hệ ngược tới showtime
    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
