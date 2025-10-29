<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $table = 'tickets';

    protected $fillable = [
        'booking_id',
        'seat_id',
        'qr_code',
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
