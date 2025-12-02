<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductStoreRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules()
    {
        return [
            'name'        => 'required|string|max:255',
            'type'        => 'required|in:combo,food,drink',
            'price'       => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'stock'       => 'required|integer|min:0',
            'is_active'   => 'boolean'
        ];
    }
}
