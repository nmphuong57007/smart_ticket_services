<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContentPostUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Đã kiểm tra middleware ở routes
    }

    public function rules()
    {
        return [
            // type là required khi update? → Tùy bạn, nhưng thường vẫn required
            'type' => 'sometimes|in:banner,news,promotion',

            'title' => 'sometimes|string|max:255',

            'short_description' => 'sometimes|nullable|string|max:500',
            'description'       => 'sometimes|nullable|string',

            // nếu gửi ảnh thì validate, không gửi thì bỏ qua
            'image' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:5120',

            'is_published' => 'sometimes|boolean',
            'published_at' => 'sometimes|nullable|date',
        ];
    }

    public function messages()
    {
        return [
            'type.in' => 'Loại nội dung không hợp lệ.',

            'title.string' => 'Tiêu đề không hợp lệ.',

            'image.image' => 'File phải là ảnh.',
            'image.mimes' => 'Ảnh phải thuộc định dạng JPG, PNG hoặc WEBP.',
            'image.max'   => 'Ảnh không được vượt quá 5MB.',
        ];
    }
}
