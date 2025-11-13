<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CinemaStoreRequest extends FormRequest
{
    /**
     * Xác thực quyền (chỉ admin được thêm rạp)
     */
    public function authorize(): bool
    {
        return $this->user() && in_array($this->user()->role, ['admin']);
    }

    /**
     * Quy tắc kiểm tra dữ liệu đầu vào
     */
    public function rules(): array
    {
        return [
            'name'    => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'phone'   => 'nullable|string|max:20',
            'status'  => 'required|in:active,inactive',
        ];
    }

    /**
     * Thông báo lỗi tùy chỉnh (hiển thị tiếng Việt)
     */
    public function messages(): array
    {
        return [
            'name.required'    => 'Tên rạp là bắt buộc.',
            'name.string'      => 'Tên rạp phải là chuỗi ký tự.',
            'name.max'         => 'Tên rạp không được vượt quá 255 ký tự.',

            'address.required' => 'Địa chỉ rạp là bắt buộc.',
            'address.string'   => 'Địa chỉ rạp phải là chuỗi ký tự.',
            'address.max'      => 'Địa chỉ rạp không được vượt quá 500 ký tự.',

            'phone.string'     => 'Số điện thoại phải là chuỗi ký tự.',
            'phone.max'        => 'Số điện thoại không được vượt quá 20 ký tự.',

            'status.required'  => 'Trạng thái là bắt buộc.',
            'status.in'        => 'Trạng thái phải là "active" hoặc "inactive".',
        ];
    }
}
