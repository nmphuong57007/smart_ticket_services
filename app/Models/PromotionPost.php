<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionPost extends Model
{
    use HasFactory;

    protected $table = 'promotion_posts';

    protected $fillable = [
        'title',
        'description',
        'slug',
        'image_url',
        'target_url',
        'published_at',
        'is_published',
        'created_by',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_published' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
