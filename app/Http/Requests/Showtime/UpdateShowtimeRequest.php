<?php

namespace App\Http\Requests\Showtime;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShowtimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Chỉ admin mới được cập nhật lịch chiếu
        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [

            // FE chỉ gửi nếu đổi phim hoặc đổi phòng
            'movie_id'  => 'sometimes|integer|exists:movies,id',
            'room_id'   => 'sometimes|integer|exists:rooms,id',

            

            'show_date' => [
                'sometimes',
                'date_format:Y-m-d',
                'after_or_equal:today', // Không cho đổi về quá khứ
            ],

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

    public function messages(): array
    {
        return [

            // movie_id
            'movie_id.integer' => 'ID phim không hợp lệ.',
            'movie_id.exists'  => 'Phim không tồn tại.',

            // room_id
            'room_id.integer' => 'ID phòng chiếu không hợp lệ.',
            'room_id.exists'  => 'Phòng chiếu không tồn tại.',

            // show_date
            'show_date.date_format'    => 'Ngày chiếu phải có dạng YYYY-MM-DD.',
            'show_date.after_or_equal' => 'Ngày chiếu phải từ hôm nay trở đi.',

            // show_time
            'show_time.date_format' => 'Giờ chiếu phải có dạng HH:MM.',

            // price
            'price.numeric' => 'Giá vé phải là số.',
            'price.min'     => 'Giá vé không được nhỏ hơn 0.',
            'price.max'     => 'Giá vé quá lớn.',

            // format
            'format.string' => 'Định dạng phim phải là chuỗi.',
            'format.max'    => 'Định dạng phim không được quá 50 ký tự.',

            // language_type
            'language_type.in' => 'Kiểu ngôn ngữ không hợp lệ (chỉ chấp nhận: sub, dub, narrated).',
        ];
    }
}
