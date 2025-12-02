<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingProduct extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'product_id',
        'quantity'
    ];

    //-------------------------
    // Relationships
    //-------------------------

    // Thuộc booking
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    // Sản phẩm
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
