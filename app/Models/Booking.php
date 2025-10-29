<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $table = 'bookings';

    protected $fillable = [
        'user_id',
        'showtime_id',
        'discount_code',
        'total_amount',
        'discount',
        'final_amount',
        'payment_status',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function showtime()
    {
        return $this->belongsTo(Showtime::class);
    }
    public function bookingProducts()
    {
        return $this->hasMany(BookingProduct::class);
    }
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
