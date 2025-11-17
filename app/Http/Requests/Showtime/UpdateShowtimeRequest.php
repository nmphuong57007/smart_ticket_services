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
            'movie_id'      => 'required|integer|exists:movies,id',
            'room_id'       => 'required|integer|exists:rooms,id',

            'cinema_id'     => 'nullable|integer|exists:cinemas,id',

            'show_date'     => 'required|date_format:Y-m-d',
            'show_time'     => 'required|date_format:H:i',
            'price'         => 'nullable|numeric|min:0',
            'format'        => 'nullable|string|max:50',

            'language_type' => 'nullable|in:sub,dub,narrated',
        ];
    }
}
