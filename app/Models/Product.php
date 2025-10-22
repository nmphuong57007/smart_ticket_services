<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'price',
        'description',
        'image',
        'stock',
        'type',
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Scope để lấy combos
    public function scopeCombos($query)
    {
        return $query->where('type', 'combo')->where('is_active', true);
    }

    // Lọc combo đang hoạt động
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Lọc theo tên
    public function scopeSearch($query, $keyword)
    {
        if ($keyword) {
            $query->where('name', 'like', "%{$keyword}%");
        }
        return $query;
    }

    // Lọc theo khoảng giá
    public function scopePriceRange($query, $min, $max)
    {
        if ($min !== null) {
            $query->where('price', '>=', $min);
        }
        if ($max !== null) {
            $query->where('price', '<=', $max);
        }
        return $query;
    }

    // Lọc theo tình trạng còn hàng
    public function scopeInStock($query, $status)
    {
        if ($status) {
            $query->where('stock', '>', 0);
        }
        return $query;
    }
}
