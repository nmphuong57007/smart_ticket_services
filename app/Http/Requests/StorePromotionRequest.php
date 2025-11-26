<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|unique:promotions,code,NULL,id',

            'discount_percent' => 'required|integer|min:1|max:100',

            // Ngày bắt đầu phải từ hôm nay trở đi
            'start_date' => 'required|date_format:Y-m-d|after_or_equal:today',

            // Ngày kết thúc phải sau ngày bắt đầu
            'end_date' => 'required|date_format:Y-m-d|after:start_date',
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
            'start_date.after_or_equal' => 'Ngày bắt đầu phải từ hôm nay trở đi.',

            // END DATE
            'end_date.required' => 'Vui lòng chọn ngày kết thúc.',
            'end_date.date_format' => 'Ngày kết thúc không đúng định dạng (Y-m-d).',
            'end_date.after' => 'Ngày kết thúc phải sau ngày bắt đầu.',
        ];
    }
}
