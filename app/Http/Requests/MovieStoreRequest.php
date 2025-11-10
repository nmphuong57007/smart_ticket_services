<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MovieStoreRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && in_array($this->user()->role, ['admin', 'staff']);
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'poster' => 'nullable|image|max:2048', // jpg/png <= 2MB
            'trailer' => 'nullable|url',
            'description' => 'nullable|string',

            // Thay vì genre dạng text, dùng genre_ids dạng mảng
            'genre_ids' => 'nullable|array',
            'genre_ids.*' => 'integer|exists:genres,id',

            'duration' => 'required|integer|min:1',
            'format' => 'required|string|max:50',
            'language' => 'required|in:dub,sub,narrated',
            'release_date' => 'required|date',
            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:release_date',
                function ($attribute, $value, $fail) {
                    if (request('release_date') && $value && $value < request('release_date')) {
                        $fail('Ngày kết thúc phải sau hoặc bằng ngày khởi chiếu.');
                    }
                }
            ],
            'status' => 'required|in:coming,showing,stopped',
        ];
    }
}
