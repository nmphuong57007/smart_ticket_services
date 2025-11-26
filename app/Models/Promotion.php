<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Promotion extends Model
{
    use HasFactory;

    protected $table = 'promotions';

    // Hằng số trạng thái (tránh dùng string thủ công)
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'code',
        'discount_percent',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Auto-set default status = active khi tạo mới
     */
    protected static function booted()
    {
        static::creating(function ($promotion) {
            if (empty($promotion->status)) {
                $promotion->status = self::STATUS_ACTIVE;
            }
        });
    }

    /**
     * Scope lọc mã đang active
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope lọc mã đã hết hạn
     */
    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED);
    }

    /**
     * Kiểm tra mã có hiệu lực hay không
     */
    public function isValid(): bool
    {
        if (!$this->start_date || !$this->end_date) {
            return false;
        }

        $now = Carbon::now();

        return $this->status === self::STATUS_ACTIVE
            && $now->between(
                $this->start_date->startOfDay(),
                $this->end_date->endOfDay()
            );
    }

    /**
     * Kiểm tra đã hết hạn chưa
     */
    public function isExpired(): bool
    {
        return $this->end_date?->isPast() ?? true;
    }

    /**
     * Tự động chuyển trạng thái thành expired nếu đã hết hạn
     */
    public function updateStatusIfExpired(): void
    {
        if ($this->isExpired() && $this->status === self::STATUS_ACTIVE) {
            $this->update(['status' => self::STATUS_EXPIRED]);
        }
    }
}
