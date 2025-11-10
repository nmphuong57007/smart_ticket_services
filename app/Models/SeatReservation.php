<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeatReservation extends Model
{
    use HasFactory;

    // Các trạng thái ghế
    public const STATUS_AVAILABLE = 'available'; // Ghế trống
    public const STATUS_RESERVED  = 'reserved';  // Ghế đang được giữ
    public const STATUS_BOOKED    = 'booked';    // Ghế đã được đặt

    // Thời gian giữ ghế tối đa (giây) = 10 phút
    public const TIMEOUT_SECONDS = 600;

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
