<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComboItem extends Model
{
    protected $fillable = ['combo_id', 'product_id', 'quantity'];

    public function combo()
    {
        return $this->belongsTo(Product::class, 'combo_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
