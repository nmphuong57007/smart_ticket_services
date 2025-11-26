<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MovieUpdateRequest extends FormRequest
{
    public function authorize()
    {

        return $this->user() && in_array($this->user()->role, ['admin']);
    }

    public function rules(): array
    {
        return [
            'title'         => 'sometimes|nullable|string|max:255',
            'poster'        => 'nullable|image|max:2048',
            'trailer'       => 'sometimes|nullable|url',
            'description'   => 'sometimes|nullable|string',

            'genre_ids'     => 'nullable|array',
            'genre_ids.*'   => 'integer|exists:genres,id',

            'duration'      => 'sometimes|nullable|integer|min:1',
            'format'        => 'sometimes|nullable|string|max:50',

            // Sửa language — LƯU FULL TEXT
            'language'      => 'sometimes|nullable|string|in:Tiếng Việt,Tiếng Anh,Tiếng Hàn,Tiếng Nhật,Tiếng Trung',

            'release_date'  => 'sometimes|nullable|date',

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

            'status'        => 'sometimes|nullable|in:coming,showing,stopped',
        ];
    }
}
