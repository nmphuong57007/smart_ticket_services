<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'title',
        'poster',
        'trailer',
        'description',
        'duration',
        'format',
        'language',       // Ngôn ngữ (dub/sub/narrated)
        'release_date',   // Ngày khởi chiếu
        'end_date',       // Ngày kết thúc
        'status',         // Trạng thái phim (coming/showing/stopped)
    ];

    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'movie_genre');
    }
    protected static function booted()
    {
        static::saving(function ($movie) {
            if ($movie->release_date && $movie->end_date && $movie->end_date < $movie->release_date) {
                throw new \Exception('Ngày kết thúc phải sau hoặc bằng ngày khởi chiếu.');
            }
        });
    }
}
