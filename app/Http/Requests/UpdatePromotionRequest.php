<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'code' => 'required|string|unique:promotions,code,' . $id,

            'discount_percent' => 'required|integer|min:1|max:100',

            // Format chuẩn Y-m-d để UI gửi đồng nhất
            'start_date' => 'required|date_format:Y-m-d',

            // Ngày kết thúc phải sau ngày bắt đầu
            'end_date' => 'required|date_format:Y-m-d|after:start_date',

            // Admin có thể vô hiệu hóa theo business logic
            'status' => 'required|in:active,expired',
        ];
    }

    public function messages(): array
    {
        return [
            // CODE
            'code.required' => 'Vui lòng nhập mã giảm giá.',
            'code.string' => 'Mã giảm giá phải là chuỗi ký tự.',
            'code.unique' => 'Mã giảm giá này đã tồn tại.',

            // DISCOUNT
            'discount_percent.required' => 'Vui lòng nhập phần trăm giảm giá.',
            'discount_percent.integer' => 'Phần trăm giảm giá phải là số nguyên.',
            'discount_percent.min' => 'Phần trăm giảm giá tối thiểu là 1%.',
            'discount_percent.max' => 'Phần trăm giảm giá tối đa là 100%.',

            // START DATE
            'start_date.required' => 'Vui lòng chọn ngày bắt đầu.',
            'start_date.date_format' => 'Ngày bắt đầu không đúng định dạng (Y-m-d).',

            // END DATE
            'end_date.required' => 'Vui lòng chọn ngày kết thúc.',
            'end_date.date_format' => 'Ngày kết thúc không đúng định dạng (Y-m-d).',
            'end_date.after' => 'Ngày kết thúc phải sau ngày bắt đầu.',

            // STATUS
            'status.required' => 'Vui lòng chọn trạng thái của mã giảm giá.',
            'status.in' => 'Trạng thái không hợp lệ. Chỉ được phép: active hoặc expired.',
        ];
    }
}
