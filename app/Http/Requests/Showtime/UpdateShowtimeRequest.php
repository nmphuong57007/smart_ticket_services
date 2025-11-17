<?php

namespace App\Http\Requests\Showtime;

use App\Models\Showtime;
use Illuminate\Foundation\Http\FormRequest;

class UpdateShowtimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $showtime = Showtime::find($this->route('id'));
        $hasTickets = $showtime->tickets()->exists();

        return [
            'movie_id' => [
                'required',
                'integer',
                'exists:movies,id',
                function ($attr, $value, $fail) use ($hasTickets, $showtime) {
                    if ($hasTickets && $value != $showtime->movie_id) {
                        $fail("Suất chiếu đã có vé — không thể đổi phim!");
                    }
                }
            ],

            'room_id' => [
                'required',
                'integer',
                'exists:rooms,id',
                function ($attr, $value, $fail) use ($hasTickets, $showtime) {
                    if ($hasTickets && $value != $showtime->room_id) {
                        $fail("Suất chiếu đã có vé — không thể đổi phòng!");
                    }
                }
            ],

            'cinema_id'     => 'nullable|integer|exists:cinemas,id',

            'show_date'     => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today', // Không cho đổi về quá khứ
            ],

            'show_time'     => [
                'required',
                'date_format:H:i',
                function ($attr, $value, $fail) {
                    if ($value < "07:00" || $value > "23:59") {
                        $fail("Giờ chiếu phải trong khoảng 07:00 đến 23:59.");
                    }
                }
            ],

            'price'         => 'nullable|numeric|min:0|max:1000000',

            'format'        => 'nullable|string|max:50',
            'language_type' => 'nullable|in:sub,dub,narrated',
        ];
    }
}
