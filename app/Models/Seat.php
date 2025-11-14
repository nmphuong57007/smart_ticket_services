<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    use HasFactory;

    protected $fillable = [
 feat/promotions_post


        'cinema_id',


        'room_id',
        'seat_code',
        'type',
        'status',
        'price',
    ];

    protected $casts = [
        'price' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Loại ghế
    public const TYPE_NORMAL = 'normal';
    public const TYPE_VIP = 'vip';

    // Trạng thái vật lý ghế
    public const STATUS_AVAILABLE   = 'available'; // Có thể sử dụng
    public const STATUS_MAINTENANCE = 'maintenance'; // Đang bảo trì
    public const STATUS_BROKEN      = 'broken'; // Hỏng
    public const STATUS_DISABLED    = 'disabled'; // Vô hiệu hoá (không sử dụng được)

    // Quan hệ
    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
