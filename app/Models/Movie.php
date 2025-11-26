<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasFactory;

    public $timestamps = true;

    /**
     * Danh sách ngôn ngữ gốc của phim — LƯU TRỰC TIẾP
     */
    const LANGUAGES = [
        'Tiếng Anh',
        'Tiếng Việt',
        'Tiếng Hàn',
        'Tiếng Nhật',
        'Tiếng Trung',
    ];

    protected $fillable = [
        'title',
        'poster',
        'trailer',
        'description',
        'duration',
        'format',      // 2D/3D/IMAX/4DX
        'language',    // LƯU TRỰC TIẾP: "Tiếng Việt", "Tiếng Anh", ...
        'release_date',
        'end_date',
        'status',
    ];

    /**
     * Quan hệ: Movie -> Genres
     */
    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'movie_genre');
    }

    /**
     * Validate ngày
     */
    protected static function booted()
    {
        static::saving(function ($movie) {
            if (
                $movie->release_date &&
                $movie->end_date &&
                $movie->end_date < $movie->release_date
            ) {
                throw new \Exception('Ngày kết thúc phải sau hoặc bằng ngày khởi chiếu.');
            }
        });
    }
}
