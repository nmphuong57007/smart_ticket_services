<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BookingSeat extends Model
{
    use HasFactory;

    protected $table = 'booking_seats';

    protected $fillable = [
        'booking_id',
        'seat_id',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class);
    }
}
