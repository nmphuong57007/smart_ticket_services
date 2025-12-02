<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules()
    {
        return [
            'name'        => 'sometimes|string|max:255',
            'type'        => 'sometimes|in:combo,food,drink',
            'price'       => 'sometimes|numeric|min:0',
            'description' => 'sometimes|string|nullable',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'stock'       => 'sometimes|integer|min:0',
            'is_active'   => 'boolean'
        ];
    }
}
