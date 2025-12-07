<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    /**
     * TRẠNG THÁI THANH TOÁN
     */
    const PAYMENT_PENDING  = 'pending';
    const PAYMENT_PAID     = 'paid';
    const PAYMENT_FAILED   = 'failed';
    const PAYMENT_REFUNDED = 'refunded';

    /**
     * TRẠNG THÁI ĐƠN HÀNG (BOOKING STATUS)
     */
    const BOOKING_PENDING  = 'pending';     // user nhấn thanh toán
    const BOOKING_PAID     = 'paid';        // thanh toán thành công
    const BOOKING_CANCELED = 'canceled';    // user hủy
    const BOOKING_EXPIRED  = 'expired';     // quá 10p không thanh toán

    protected $fillable = [
        'user_id',
        'showtime_id',
        'discount_code',
        'total_amount',
        'discount',
        'final_amount',
        'payment_status', // trạng thái thanh toán
        'booking_status', // trạng thái đơn hàng
        'booking_code',
    ];

    protected $casts = [
        'total_amount'  => 'float',
        'discount'      => 'float',
        'final_amount'  => 'float',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    // Quan hệ
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function showtime()
    {
        return $this->belongsTo(Showtime::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function products()
    {
        return $this->hasMany(BookingProduct::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
    public function bookingSeats()
    {
        return $this->hasMany(BookingSeat::class);
    }
    public function seats()
    {
        return $this->belongsToMany(Seat::class, 'booking_seats');
    }
}
