<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'type',
        'title',
        'image',
        'description',
        'created_at',
    ];
}
