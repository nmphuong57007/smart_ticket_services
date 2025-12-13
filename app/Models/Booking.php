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

    // Quan hệ user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Quan hệ showtime
    public function showtime()
    {
        return $this->belongsTo(Showtime::class);
    }

    // Quan hệ ticket
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    // Quan hệ sản phẩm đã mua trong booking
    public function products()
    {
        return $this->hasMany(BookingProduct::class);
    }

    // Quan hệ thanh toán
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Quan hệ booking_seats
    public function bookingSeats()
    {
        return $this->hasMany(BookingSeat::class);
    }

    // Lấy danh sách ghế qua bảng trung gian booking_seats
    public function seats()
    {
        return $this->belongsToMany(Seat::class, 'booking_seats', 'booking_id', 'seat_id');
    }

    // Lấy ticket liên quan (1 booking có thể có nhiều ticket, nhưng lấy 1 ticket để check-in)
    public function ticket()
    {
        return $this->hasOne(Ticket::class, 'booking_id');
    }
}
