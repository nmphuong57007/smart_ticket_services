<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    use HasFactory;

    protected $fillable = [
        'showtime_id',     // GHẾ THEO SUẤT CHIẾU
        'seat_code',
        'type',
        'status',
        'price',
    ];

    protected $casts = [
        'price'       => 'float',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    // Loại ghế
    public const TYPE_NORMAL = 'normal';
    public const TYPE_VIP    = 'vip';

    // Trạng thái dùng khi BOOKING
    public const STATUS_AVAILABLE = 'available'; // Chưa ai chọn
    public const STATUS_SELECTED  = 'selected';  // Client đang chọn (chưa thanh toán)
    public const STATUS_BOOKED    = 'booked';    // Đã mua

    /**
     * GHẾ THUỘC SUẤT CHIẾU
     */
    public function showtime()
    {
        return $this->belongsTo(Showtime::class);
    }
}
