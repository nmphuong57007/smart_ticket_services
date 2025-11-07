<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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

    const STATUS_AVAILABLE = 'available';
    const STATUS_RESERVED  = 'reserved';
    const STATUS_BOOKED    = 'booked';
    const RESERVED_TIMEOUT = 10; // phút

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
     * Nếu đã eager load reservations, dùng collection -> tránh query lại DB
     */
    public function getCurrentStatusAttribute(): string
    {
        $timeout = now()->subMinutes(self::RESERVED_TIMEOUT);

        if ($this->relationLoaded('reservations')) {
            $active = $this->reservations->filter(function ($r) use ($timeout) {
                return $r->status === self::STATUS_BOOKED
                    || ($r->status === self::STATUS_RESERVED && $r->reserved_at > $timeout);
            })->sortByDesc('reserved_at')->first();

            return $active->status ?? self::STATUS_AVAILABLE;
        }

        // Nếu chưa eager load, query trực tiếp
        $reservation = $this->reservations()
            ->where(function ($q) use ($timeout) {
                $q->where('status', self::STATUS_BOOKED)
                    ->orWhere(function ($q2) use ($timeout) {
                        $q2->where('status', self::STATUS_RESERVED)
                            ->where('reserved_at', '>', $timeout);
                    });
            })
            ->latest('reserved_at')
            ->first();

        return $reservation->status ?? self::STATUS_AVAILABLE;
    }

    /**
     * Scope eager load reservation còn hiệu lực
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
