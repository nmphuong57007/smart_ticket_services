<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    use HasFactory;

    protected $fillable = [


        'cinema_id',

        'room_id',
        'seat_code',
        'type',
        'status',
        'price',
    ];

    protected $casts = [
        'price' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Trạng thái ghế
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_RESERVED  = 'reserved';
    public const STATUS_BOOKED    = 'booked';

    public const RESERVED_TIMEOUT = 10; // phút

    // Relationships
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function cinema()
    {
        return $this->belongsTo(Cinema::class);
    }

    public function reservations()
    {
        return $this->hasMany(SeatReservation::class);
    }

    /**
     * Trạng thái hiện tại của ghế
     */
    public function getCurrentStatusAttribute(): string
    {
        $timeout = now()->subMinutes(self::RESERVED_TIMEOUT);

        $reservation = $this->reservations
            ->where('status', self::STATUS_BOOKED)
            ->first()
            ?? $this->reservations
                ->where('status', self::STATUS_RESERVED)
                ->where('reserved_at', '>', $timeout)
                ->first();

        return $reservation ? $reservation->status : self::STATUS_AVAILABLE;
    }

    /**
     * Trạng thái ghế cho 1 suất chiếu cụ thể
     */
    public function getStatusForShowtime(int $showtimeId): string
    {
        $timeout = now()->subMinutes(self::RESERVED_TIMEOUT);

        $reservation = $this->reservations()
            ->where('showtime_id', $showtimeId)
            ->where(function ($q) use ($timeout) {
                $q->where('status', self::STATUS_BOOKED)
                  ->orWhere(function ($q2) use ($timeout) {
                      $q2->where('status', self::STATUS_RESERVED)
                         ->where('reserved_at', '>', $timeout);
                  });
            })
            ->latest('reserved_at')
            ->first();

        return $reservation ? $reservation->status : self::STATUS_AVAILABLE;
    }

    /**
     * Scope: eager load reservation còn hiệu lực
     */
    public function scopeWithActiveReservation($query)
    {
        $timeout = now()->subMinutes(self::RESERVED_TIMEOUT);

        return $query->with(['reservations' => function ($q) use ($timeout) {
            $q->where('status', self::STATUS_BOOKED)
              ->orWhere(function ($q2) use ($timeout) {
                  $q2->where('status', self::STATUS_RESERVED)
                     ->where('reserved_at', '>', $timeout);
              });
        }]);
    }
}
