<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContentPostStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true; // đã kiểm tra role ở route middleware
    }

    public function rules()
    {
        return [
            'type' => 'required|in:banner,news,promotion',

            'title' => 'required|string|max:255',

            'short_description' => 'nullable|string|max:500',
            'description'       => 'nullable|string',

            // ảnh có thể không upload (VD: tin tức không bắt buộc ảnh)
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',

            'is_published' => 'boolean',
            'published_at' => 'nullable|date'
        ];
    }

    public function messages()
    {
        return [
            'type.required' => 'Loại nội dung không được để trống.',
            'type.in'       => 'Loại nội dung không hợp lệ.',

            'title.required' => 'Tiêu đề không được để trống.',

            'image.image' => 'File phải là ảnh.',
            'image.mimes' => 'Ảnh phải thuộc định dạng JPG, PNG hoặc WEBP.',
            'image.max'   => 'Ảnh không được vượt quá 5MB.',
        ];
    }
}
