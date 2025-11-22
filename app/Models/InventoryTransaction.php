<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'product_id',
        'change',
        'type',
        'reference',
        'note',
        'created_by',
        'created_at'
    ];

    public $timestamps = false; // vì mình chỉ dùng created_at tự động

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
