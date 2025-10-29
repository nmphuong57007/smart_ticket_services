<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;
    protected $table = 'promotions';
    protected $fillable = [
        'code',
        'discount_percent',
        'start_date',
        'end_date',
        'status',
    ];


    public function isValid()
    {
        $now = now();
        return $this->status === 'active' && $now->between($this->start_date, $this->end_date);
    }
}
