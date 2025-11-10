<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{
    BelongsTo,
    HasMany,
    HasManyThrough
};

class Room extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'cinema_id',
        'name',
        'seat_map',
        'total_seats',
        'status',
    ];

    protected $casts = [
        'seat_map' => 'array',
        'total_seats' => 'integer',
    ];

    protected $attributes = [
        'status' => 'active',
    ];

    /**
     * Booted: tự tính total_seats mỗi khi save
     */
    protected static function booted()
    {
        static::saving(function ($room) {
            if (!empty($room->seat_map)) {
                $room->total_seats = $room->computeTotalSeats($room->seat_map);
            } else {
                $room->total_seats = 0;
            }
        });
    }

    /**
     * Quan hệ với Cinema
     */
    public function cinema(): BelongsTo
    {
        return $this->belongsTo(Cinema::class);
    }

    /**
     * Quan hệ với showtimes
     */
    public function showtimes(): HasMany
    {
        return $this->hasMany(Showtime::class);
    }

    /**
     * Quan hệ với seats thông qua showtimes
     */
    public function seats()
    {
        return $this->hasMany(Seat::class);
    }

    /**
     * Setter seat_map: luôn cast array
     */
    public function setSeatMapAttribute($value): void
    {
        $map = is_array($value) ? $value : json_decode($value, true);
        $this->attributes['seat_map'] = json_encode($map ?? []);
        // total_seats sẽ tự tính trong booted() khi save
    }

    /**
     * Tính tổng số ghế dựa trên seat_map
     */
    public function computeTotalSeats(array $seatMap): int
    {
        $count = 0;
        foreach ($seatMap as $row) {
            if (!is_array($row)) continue;
            foreach ($row as $seat) {
                if (is_string($seat)) {
                    $count++;
                } elseif (is_array($seat) && !empty($seat['code'])) {
                    $count++;
                }
            }
        }
        return $count;
    }

    /**
     * Scope lọc theo trạng thái
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope lọc theo rạp
     */
    public function scopeCinema($query, int $cinemaId)
    {
        return $query->where('cinema_id', $cinemaId);
    }
}
