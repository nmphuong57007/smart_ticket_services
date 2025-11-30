<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    // Các trạng thái thanh toán
    const STATUS_PENDING  = 'pending';
    const STATUS_PAID     = 'paid';
    const STATUS_FAILED   = 'failed';
    const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'user_id',
        'showtime_id',
        'discount_code',
        'total_amount',
        'discount',
        'final_amount',
        'payment_status',
    ];

    protected $casts = [
        'total_amount'  => 'float',
        'discount'      => 'float',
        'final_amount'  => 'float',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    //-------------------------
    // Relationships
    //-------------------------

    // Booking thuộc về user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Booking thuộc về suất chiếu
    public function showtime()
    {
        return $this->belongsTo(Showtime::class);
    }

    // Booking có nhiều vé
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    // Booking có nhiều sản phẩm mua thêm
    public function products()
    {
        return $this->hasMany(BookingProduct::class);
    }

    // Một booking có thể có nhiều payments (nếu bạn làm thanh toán)
    // public function payments()
    // {
    //     return $this->hasMany(Payment::class);
    // }
}
