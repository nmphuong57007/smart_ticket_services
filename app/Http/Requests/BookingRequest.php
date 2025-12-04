<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'showtime_id' => 'required|exists:showtimes,id',

            // GHẾ
            'seats' => 'required|array|min:1',
            'seats.*' => 'required|distinct|exists:seats,id',

            // SẢN PHẨM
            'products' => 'nullable|array',
            'products.*.product_id' => 'required_with:products|exists:products,id|distinct',
            'products.*.qty' => 'required_with:products|integer|min:1',

            // MÃ GIẢM GIÁ
            'discount_code' => 'nullable|string|max:191'
        ];
    }

    public function messages()
    {
        return [
            'seats.*.distinct' => 'Danh sách ghế bị trùng.',
            'products.*.product_id.distinct' => 'Sản phẩm bị trùng, vui lòng gộp lại.',
        ];
    }
}
