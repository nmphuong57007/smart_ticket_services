<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Promotion extends Model
{
    use HasFactory;

    protected $table = 'promotions';

    public const STATUS_ACTIVE   = 'active';
    public const STATUS_UPCOMING = 'upcoming';
    public const STATUS_EXPIRED  = 'expired';
    public const STATUS_DISABLED = 'disabled';

    protected $fillable = [
        'code',
        'type',
        'discount_percent',
        'discount_amount',
        'max_discount_amount',
        'usage_limit',
        'used_count',
        'movie_id',
        'min_order_amount',
        'start_date',
        'end_date',
        'status', // giá trị DB: active / disabled
    ];

    protected $casts = [
        'discount_percent'     => 'integer',
        'discount_amount'      => 'integer',
        'max_discount_amount'  => 'integer',
        'usage_limit'          => 'integer',
        'used_count'           => 'integer',
        'movie_id'             => 'integer',
        'min_order_amount'     => 'integer',
        'start_date'           => 'date',
        'end_date'             => 'date',
        'created_at'           => 'datetime',
        'updated_at'           => 'datetime',
    ];


    /* ========================================
       STATUS TÍNH TOÁN RUNTIME (KHÔNG GHI DB)
    ======================================== */
    public function getRuntimeStatusAttribute()
    {
        // Nếu admin tắt → luôn disabled
        if ($this->status === self::STATUS_DISABLED) {
            return self::STATUS_DISABLED;
        }

        // Nếu chưa đủ dữ liệu → active mặc định
        if (!$this->start_date || !$this->end_date) {
            return self::STATUS_ACTIVE;
        }

        $start = Carbon::parse($this->start_date)->startOfDay();
        $end   = Carbon::parse($this->end_date)->endOfDay();
        $today = today()->startOfDay();

        if ($today->lt($start)) {
            return self::STATUS_UPCOMING;
        }

        if ($today->gt($end)) {
            return self::STATUS_EXPIRED;
        }

        return self::STATUS_ACTIVE;
    }


    public function getRuntimeStatusLabelAttribute()
    {
        return [
            self::STATUS_ACTIVE   => 'Đang áp dụng',
            self::STATUS_UPCOMING => 'Sắp áp dụng',
            self::STATUS_EXPIRED  => 'Đã hết hạn',
            self::STATUS_DISABLED => 'Đã vô hiệu hóa',
        ][$this->runtime_status] ?? 'Không xác định';
    }


    /* ========================================
       KIỂM TRA MÃ HỢP LỆ
    ======================================== */
    public function getIsValidAttribute(): bool
    {
        if ($this->runtime_status !== self::STATUS_ACTIVE) {
            return false;
        }

        if (!$this->start_date || !$this->end_date) {
            return false;
        }

        $start = Carbon::parse($this->start_date)->startOfDay();
        $end   = Carbon::parse($this->end_date)->endOfDay();
        $today = today()->startOfDay();

        if (!$today->between($start, $end)) {
            return false;
        }

        if ($this->usage_limit > 0 && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }


    public function getIsExpiredAttribute(): bool
    {
        if (!$this->end_date) {
            return false;
        }

        return today()->startOfDay()->gt(
            Carbon::parse($this->end_date)->endOfDay()
        );
    }
}
