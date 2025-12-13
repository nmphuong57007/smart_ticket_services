<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $table = 'tickets';

    // Theo ảnh phpMyAdmin: bảng tickets không có created_at / updated_at
    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'qr_code',
        'is_checked_in',
        'checked_in_at',
        'checked_in_by',
    ];

    protected $casts = [
        'is_checked_in' => 'boolean',
        'checked_in_at' => 'datetime',
    ];

    /**
     * Ticket -> Booking (1-1)
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Ticket -> Seats (THÔNG QUA booking_id)
     * 1 vé có nhiều ghế
     */
    public function bookingSeats()
    {
        return $this->hasMany(
            BookingSeat::class,
            'booking_id',
            'booking_id'
        );
    }

    /**
     * Ticket -> Products (THÔNG QUA booking_id)
     * 1 vé có nhiều combo / bắp nước
     */
    public function bookingProducts()
    {
        return $this->hasMany(
            BookingProduct::class,
            'booking_id',
            'booking_id'
        );
    }

    /**
     * Nhân viên đã check-in vé
     */
    public function checkedInBy()
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }
}
