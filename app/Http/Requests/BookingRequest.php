<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Đã check auth bên controller
    }

    public function rules()
    {
        return [
            'showtime_id' => 'required|exists:showtimes,id',

            'seats' => 'required|array|min:1',
            'seats.*' => 'required|exists:seats,id',

            'products' => 'nullable|array',
            'products.*.product_id' => 'required_with:products|exists:products,id',
            'products.*.qty' => 'required_with:products|integer|min:1',

            'discount_code' => 'nullable|string|max:191',
        ];
    }
}
