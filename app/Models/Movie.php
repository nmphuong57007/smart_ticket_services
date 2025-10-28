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
        'genre',
        'duration',
        'format',
        'language',       // Ngôn ngữ (dub/sub/narrated)
        'release_date',   // Ngày khởi chiếu
        'end_date',       // Ngày kết thúc
        'status',         // Trạng thái phim (coming/showing/stopped)
    ];
}
