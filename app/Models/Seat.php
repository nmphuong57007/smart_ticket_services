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

    /**
     * LOẠI GHẾ
     */
    public const TYPE_NORMAL = 'normal';
    public const TYPE_VIP    = 'vip';

    /**
     * TRẠNG THÁI GHẾ THEO NGHIỆP VỤ ĐẶT VÉ
     */
    public const STATUS_AVAILABLE        = 'available';        // Chưa ai chọn
    public const STATUS_SELECTED         = 'selected';         // FE tạm chọn
    public const STATUS_PENDING_PAYMENT  = 'pending_payment';  // Giữ ghế khi user nhấn THANH TOÁN
    public const STATUS_BOOKED           = 'booked';           // Đã thanh toán thành công

    /**
     * GHẾ THUỘC SUẤT CHIẾU
     */
    public function showtime()
    {
        return $this->belongsTo(Showtime::class);
    }

    /**
     * Helper: kiểm tra ghế có đang available không
     */
    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    /**
     * Helper: ghế đã được giữ trong quá trình thanh toán?
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING_PAYMENT;
    }

    /**
     * Helper: ghế đã được đặt mua chưa?
     */
    public function isBooked(): bool
    {
        return $this->status === self::STATUS_BOOKED;
    }
}
