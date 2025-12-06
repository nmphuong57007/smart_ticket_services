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

            // Mã giảm giá duy nhất
            'code' => 'required|string|unique:promotions,code',

            // Loại mã giảm giá: percent hoặc money
            'type' => 'required|in:percent,money',

            // Giảm theo % (bắt buộc nếu type = percent)
            'discount_percent' => 'required_if:type,percent|integer|min:1|max:100',

            // Giảm theo tiền (bắt buộc nếu type = money)
            'discount_amount' => 'required_if:type,money|integer|min:1',

            // Số tiền giảm tối đa (tùy chọn)
            'max_discount_amount' => 'nullable|integer|min:1',

            // Giới hạn số lượt dùng
            'usage_limit' => 'nullable|integer|min:0',

            // Áp dụng cho phim nào (nullable = mọi phim)
            'movie_id' => 'nullable|integer|exists:movies,id',

            // Điều kiện tối thiểu để áp mã
            'min_order_amount' => 'nullable|integer|min:0',

            // Ngày bắt đầu
            'start_date' => 'required|date_format:Y-m-d|after_or_equal:today',

            // Ngày kết thúc
            'end_date' => 'required|date_format:Y-m-d|after:start_date',
        ];
    }

    public function messages(): array
    {
        return [

            // CODE
            'code.required' => 'Vui lòng nhập mã giảm giá.',
            'code.string'   => 'Mã giảm giá phải là chuỗi ký tự.',
            'code.unique'   => 'Mã giảm giá này đã tồn tại.',

            // TYPE
            'type.required' => 'Vui lòng chọn loại mã giảm giá.',
            'type.in'       => 'Loại mã giảm giá không hợp lệ.',

            // DISCOUNT PERCENT
            'discount_percent.required_if' => 'Vui lòng nhập phần trăm giảm giá.',
            'discount_percent.integer'     => 'Phần trăm giảm giá phải là số nguyên.',
            'discount_percent.min'         => 'Phần trăm giảm giá tối thiểu là 1%.',
            'discount_percent.max'         => 'Phần trăm giảm giá tối đa là 100%.',

            // DISCOUNT AMOUNT
            'discount_amount.required_if' => 'Vui lòng nhập số tiền giảm.',
            'discount_amount.integer'     => 'Số tiền giảm phải là số nguyên.',
            'discount_amount.min'         => 'Số tiền giảm phải lớn hơn 0.',

            // MAX DISCOUNT
            'max_discount_amount.integer' => 'Mức giảm tối đa phải là số nguyên.',
            'max_discount_amount.min'     => 'Mức giảm tối đa phải lớn hơn 0.',

            // USAGE LIMIT
            'usage_limit.integer' => 'Giới hạn lượt dùng phải là số nguyên.',
            'usage_limit.min'     => 'Giới hạn lượt dùng không được âm.',

            // MOVIE ID
            'movie_id.integer' => 'Phim áp dụng không hợp lệ.',
            'movie_id.exists'  => 'Phim không tồn tại trong hệ thống.',

            // MIN ORDER AMOUNT
            'min_order_amount.integer' => 'Số tiền tối thiểu phải là số nguyên.',
            'min_order_amount.min'     => 'Số tiền tối thiểu không được âm.',

            // START DATE
            'start_date.required'      => 'Vui lòng chọn ngày bắt đầu.',
            'start_date.date_format'   => 'Ngày bắt đầu không đúng định dạng Y-m-d.',
            'start_date.after_or_equal' => 'Ngày bắt đầu phải từ hôm nay trở đi.',

            // END DATE
            'end_date.required'      => 'Vui lòng chọn ngày kết thúc.',
            'end_date.date_format'   => 'Ngày kết thúc không đúng định dạng Y-m-d.',
            'end_date.after'         => 'Ngày kết thúc phải sau ngày bắt đầu.',
        ];
    }
}
