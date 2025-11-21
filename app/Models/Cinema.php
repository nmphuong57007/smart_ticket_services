<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cinema extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'status',
    ];

    /**
     * MỘT RẠP DUY NHẤT – Luôn truy vấn ID = 1
     * Giúp các chỗ khác không cần load nhiều rạp
     */
    public static function default()
    {
        return self::find(1);
    }

    /**
     * Scope lọc rạp đang hoạt động
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Rạp có nhiều phòng chiếu
     */
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}
