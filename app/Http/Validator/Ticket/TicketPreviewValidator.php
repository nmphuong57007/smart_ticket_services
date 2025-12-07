<?php

namespace App\Http\Validator\Ticket;

use App\Http\Validator\BaseValidator;
use App\Models\Seat;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Support\Facades\Validator;

class TicketPreviewValidator extends BaseValidator
{
    /**
     * Quy tắc validation
     */
    public function rules(): array
    {
        return [
            'showtime_id' => 'required|integer|exists:showtimes,id',

            'seat_ids' => 'required|array|min:1',
            'seat_ids.*' => 'integer',

            'combo_ids' => 'sometimes|array',
            'combo_ids.*' => 'integer',

            //  Thêm rule cho mã giảm giá
            'promotion_code' => 'sometimes|string|nullable',
        ];
    }

    /**
     * Thông báo lỗi
     */
    public function messages(): array
    {
        return [
            'showtime_id.required' => 'Vui lòng chọn lịch chiếu',
            'showtime_id.integer' => 'ID lịch chiếu phải là số nguyên',
            'showtime_id.exists' => 'Lịch chiếu không tồn tại',

            'seat_ids.required' => 'Vui lòng chọn ít nhất 1 ghế',
            'seat_ids.array' => 'Danh sách ghế phải là mảng',
            'seat_ids.*.integer' => 'ID ghế phải là số nguyên',

            'combo_ids.array' => 'Danh sách combo phải là mảng',
            'combo_ids.*.integer' => 'ID combo phải là số nguyên',

            'promotion_code.string' => 'Mã giảm giá không hợp lệ',
        ];
    }

    /**
     * Validate dữ liệu và check ghế + combo tồn tại + còn trống/stock
     */
    public function validateWithStatus(array $data): array
    {
        $validator = Validator::make($data, $this->rules(), $this->messages());

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->errors()->toArray(),
            ];
        }

        $validated = $validator->validated();

        /**
         *  Validate ghế
         */
        if (!empty($validated['seat_ids'])) {
            $seatCount = Seat::whereIn('id', $validated['seat_ids'])
                ->where('showtime_id', $validated['showtime_id'])
                ->where('status', 'available')
                ->count();

            if ($seatCount !== count($validated['seat_ids'])) {
                return [
                    'success' => false,
                    'errors' => [
                        'seat_ids' => ['Một số ghế không tồn tại, không thuộc lịch chiếu hoặc đã được đặt'],
                    ],
                ];
            }
        }

        /**
         *  Validate combo
         */
        if (!empty($validated['combo_ids'])) {
            $comboCount = Product::whereIn('id', $validated['combo_ids'])
                ->where('is_active', true)
                ->where('stock', '>', 0)
                ->count();

            if ($comboCount !== count($validated['combo_ids'])) {
                return [
                    'success' => false,
                    'errors' => [
                        'combo_ids' => ['Một số combo không tồn tại, đã ngừng hoạt động hoặc hết hàng'],
                    ],
                ];
            }
        }

        /**
         *  Validate mã khuyến mãi (nếu có)
         */
        if (!empty($validated['promotion_code'])) {
            $promotion = Promotion::where('code', $validated['promotion_code'])
                ->where('status', 'active')
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())
                ->first();

            if (!$promotion) {
                return [
                    'success' => false,
                    'errors' => [
                        'promotion_code' => ['Mã giảm giá không hợp lệ hoặc đã hết hạn'],
                    ],
                ];
            }
        }

        return [
            'success' => true,
            'data' => $validated,
        ];
    }
}
