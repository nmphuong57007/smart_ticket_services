<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    use HasFactory;

    protected $fillable = [
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
    public const TYPE_STANDARD = 'standard';
    public const TYPE_VIP = 'vip';
    public const TYPE_DOUBLE = 'double';

    // Trạng thái ghế (vật lý)
    public const STATUS_AVAILABLE = 'available';    // Sẵn sàng sử dụng
    public const STATUS_MAINTENANCE = 'maintenance'; // Đang bảo trì
    public const STATUS_DISABLED = 'disabled';      // Không sử dụng được

    // Quan hệ
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function cinema()
    {
        return $this->belongsTo(Cinema::class);
    }
}
