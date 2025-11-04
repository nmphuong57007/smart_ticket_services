<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MovieStoreRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && in_array($this->user()->role, ['admin']);
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'poster' => 'nullable|image|max:2048', // jpg/png <= 2MB
            'trailer' => 'nullable|url',
            'description' => 'nullable|string',
            'genre' => 'nullable|string|max:100',
            'duration' => 'required|integer|min:1',
            'format' => 'required|string|max:50',
            'language' => 'required|in:dub,sub,narrated',
            'release_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:release_date',

            'status' => 'required|in:coming,showing,stopped',
        ];
    }
}
