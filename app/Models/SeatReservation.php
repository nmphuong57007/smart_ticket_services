<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SeatReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'showtime_id',
        'seat_id',
        'user_id',
        'status',
        'reserved_at',
        'booked_at',
    ];

    protected $casts = [
        'reserved_at' => 'datetime',
        'booked_at' => 'datetime',
    ];

    public function seat()
    {
        return $this->belongsTo(Seat::class);
    }
    public function showtime()
    {
        return $this->belongsTo(Showtime::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scope lọc reservation còn hiệu lực
    public function scopeActive($query, int $timeoutMinutes = 10)
    {
        $threshold = now()->subMinutes($timeoutMinutes);
        return $query->where('status', 'booked')
            ->orWhere(fn($q) => $q->where('status', 'reserved')
                ->where('reserved_at', '>', $threshold));
    }
}
