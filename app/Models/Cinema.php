<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cinema extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'address', 'phone', 'status']; // + status

    // (tuỳ chọn) scope lọc active
    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}
