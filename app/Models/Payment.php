<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'booking_id',
        'user_id',
        'method',
        'amount',
        'status',
        'transaction_code',
        'transaction_uuid',
        'bank_code',
        'pay_url',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime'
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
