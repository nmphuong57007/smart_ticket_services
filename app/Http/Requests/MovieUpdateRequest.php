<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MovieUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && in_array($this->user()->role, ['admin', 'staff']);
    }

    public function rules(): array
    {
        return [
            'title'         => 'sometimes|nullable|string|max:255',
            'poster'        => 'nullable|image|max:2048',
            'trailer'       => 'sometimes|nullable|url',
            'description'   => 'sometimes|nullable|string',

            // Thay thế 'genre' bằng mảng ID thể loại
            'genre_ids'     => 'nullable|array',
            'genre_ids.*'   => 'integer|exists:genres,id',

            'duration'      => 'sometimes|nullable|integer|min:1',
            'format'        => 'sometimes|nullable|string|max:50',
            'language'      => 'sometimes|nullable|in:dub,sub,narrated',
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
