<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Genre extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'is_active'];
    protected $attributes = [
        'is_active' => true,
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Tự động tạo và cập nhật slug
    protected static function booted()
    {
        // Khi tạo thể loại mới: tạo slug nếu chưa có
        static::creating(function ($genre) {
            if (empty($genre->slug) && !empty($genre->name)) {
                $genre->slug = Str::slug($genre->name);
            }
        });

        // Khi cập nhật tên: cập nhật slug tương ứng
        static::updating(function ($genre) {
            if ($genre->isDirty('name') && !empty($genre->name)) {
                $genre->slug = Str::slug($genre->name);
            }
        });
    }

    // Quan hệ với Movie (Nhiều-Nhiều)
    public function movies()
    {
        return $this->belongsToMany(Movie::class, 'movie_genre');
    }
}
