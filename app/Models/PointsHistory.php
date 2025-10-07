<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointsHistory extends Model
{
    protected $table = 'points_history';
    
    protected $fillable = [
        'user_id',
        'points',
        'balance_before',
        'balance_after',
        'type',
        'source',
        'reference_type',
        'reference_id',
        'description',
        'metadata',
        'created_by'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Accessor cho metadata để tự động decode JSON
     */
    public function getMetadataAttribute($value)
    {
        return $value ? json_decode($value, true) : null;
    }

    /**
     * Mutator cho metadata để tự động encode JSON
     */
    public function setMetadataAttribute($value)
    {
        $this->attributes['metadata'] = $value ? json_encode($value) : null;
    }

    /**
     * Relationship với User (người dùng)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship với User (người thực hiện)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope để lọc theo loại giao dịch
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope để lọc theo nguồn
     */
    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope để sắp xếp mới nhất trước
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Accessor để hiển thị loại giao dịch bằng tiếng Việt
     */
    public function getTypeNameAttribute(): string
    {
        $typeNames = [
            'earned' => 'Tích điểm',
            'spent' => 'Sử dụng điểm',
            'refunded' => 'Hoàn điểm',
            'bonus' => 'Thưởng',
            'penalty' => 'Phạt điểm'
        ];

        return $typeNames[$this->type] ?? $this->type;
    }

    /**
     * Accessor để hiển thị nguồn bằng tiếng Việt
     */
    public function getSourceNameAttribute(): string
    {
        $sourceNames = [
            'booking' => 'Đặt vé',
            'promotion' => 'Khuyến mãi',
            'manual' => 'Cập nhật thủ công',
            'referral' => 'Giới thiệu bạn bè',
            'review' => 'Đánh giá phim',
            'birthday' => 'Quà sinh nhật',
            'registration' => 'Đăng ký mới'
        ];

        return $sourceNames[$this->source] ?? $this->source;
    }

    /**
     * Accessor để hiển thị thông tin người thực hiện
     */
    public function getPerformedByAttribute(): ?string
    {
        if ($this->creator) {
            return "Nhân viên: {$this->creator->fullname} ({$this->creator->email})";
        }
        
        // Hiển thị theo loại nguồn
        $autoSources = [
            'booking' => 'Tự động từ hệ thống đặt vé',
            'promotion' => 'Tự động từ chương trình khuyến mãi',
            'referral' => 'Tự động từ hệ thống giới thiệu',
            'review' => 'Tự động từ hệ thống đánh giá',
            'birthday' => 'Tự động từ hệ thống sinh nhật',
            'registration' => 'Tự động khi đăng ký tài khoản'
        ];
        
        return $autoSources[$this->source] ?? 'Tự động từ hệ thống';
    }

    /**
     * Accessor để hiển thị mô tả đầy đủ
     */
    public function getFullDescriptionAttribute(): string
    {
        $description = $this->description;
        
        if ($this->source === 'manual' && $this->creator) {
            $description .= " (Thực hiện bởi: {$this->creator->fullname})";
        }
        
        return $description;
    }

    /**
     * Scope để lấy kèm thông tin người thực hiện
     */
    public function scopeWithCreator($query)
    {
        return $query->with(['creator:id,fullname,email,role']);
    }
}
