<?php

namespace App\Http\Requests\Showtime;


use Illuminate\Foundation\Http\FormRequest;

class UpdateShowtimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {


        return [

            // Movie & room: chỉ validate khi FE gửi lên
            'movie_id'  => 'sometimes|integer|exists:movies,id',
            'room_id'   => 'sometimes|integer|exists:rooms,id',

            'cinema_id' => 'sometimes|nullable|integer|exists:cinemas,id',

            'show_date' => 'sometimes|date_format:Y-m-d|after_or_equal:today',

            'show_time' => [
                'sometimes',
                'date_format:H:i',
                function ($attr, $value, $fail) {
                    if ($value < "08:00" || $value > "23:59") {
                        $fail("Giờ chiếu phải trong khoảng 08:00 đến 23:59.");
                    }
                }
            ],

            'price'         => 'sometimes|numeric|min:0|max:1000000',
            'format'        => 'sometimes|string|max:50',
            'language_type' => 'sometimes|in:sub,dub,narrated',
        ];
    }
}
