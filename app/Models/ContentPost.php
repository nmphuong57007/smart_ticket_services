<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class ContentPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'short_description',
        'description',
        'slug',
        'image',
        'is_published',
        'published_at',
        'created_by',
        'created_by_name'
    ];

    protected $table = 'content_posts';

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    // Quan hệ với user tạo bài
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
