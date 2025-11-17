<?php

namespace App\Http\Requests\Showtime;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShowtimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'movie_id'      => 'required|integer|exists:movies,id',
            'room_id'       => 'required|integer|exists:rooms,id',

            'cinema_id'     => 'nullable|integer|exists:cinemas,id',

            'show_date'     => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today', // NGĂN TẠO NGÀY QUÁ KHỨ
            ],

            'show_time'     => [
                'required',
                'date_format:H:i',
                function ($attr, $value, $fail) {
                    if ($value < "07:00" || $value > "23:59") {
                        $fail("Giờ chiếu phải trong khoảng 08:00 đến 23:59.");
                    }
                }
            ],

            'price'         => 'nullable|numeric|min:0|max:1000000',

            'format'        => 'nullable|string|max:50',

            'language_type' => 'nullable|in:sub,dub,narrated',
        ];
    }
}
