<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Seat extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'cinema_id',   // thêm
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

    // Quan hệ ngược tới phòng
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    // Quan hệ ngược tới rạp
    public function cinema()
    {
        return $this->belongsTo(Cinema::class);
    }

    // Quan hệ tới các lượt giữ/đặt ghế
    public function reservations()
    {
        return $this->hasMany(SeatReservation::class);
    }

    /**
     * Lấy trạng thái hiện tại của ghế theo thời điểm thực tế
     */
    public function getCurrentStatusAttribute(): string
    {
        // Kiểm tra xem có reservation nào còn hiệu lực không (10 phút)
        $reservation = $this->reservations()
            ->where(function ($q) {
                $q->where('status', self::STATUS_BOOKED)
                    ->orWhere(function ($q2) {
                        $q2->where('status', self::STATUS_RESERVED)
                            ->where('reserved_at', '>', Carbon::now()->subMinutes(10));
                    });
            })
            ->latest('reserved_at')
            ->first();

        return $reservation->status ?? $this->status ?? self::STATUS_AVAILABLE;
    }
}
