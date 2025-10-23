<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cinema extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'created_at',
    ];

    /**
     * Lấy danh sách phòng của rạp
     */
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}