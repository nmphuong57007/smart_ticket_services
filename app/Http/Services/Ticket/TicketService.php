<?php

namespace App\Http\Services\Ticket;

use App\Models\Seat;
use App\Models\Product;
use App\Models\Showtime;
use App\Models\Promotion;

class TicketService
{
    public function previewTicket(int $showtimeId, array $seatIds = [], array $comboIds = [], ?string $promotionCode = null): array
    {
        // Lấy thông tin showtime + room + cinema + movie
        $showtime = Showtime::with(['room.cinema', 'movie'])
            ->find($showtimeId);

        if (!$showtime) {
            return [
                'success' => false,
                'message' => 'Showtime không tồn tại',
                'data' => []
            ];
        }

        // Lấy thông tin ghế KHÔNG FILTER theo status
        $seats = collect();
        if (!empty($seatIds)) {
            $seats = Seat::whereIn('id', $seatIds)
                ->where('showtime_id', $showtimeId)
                ->get()
                ->map(function ($seat) {
                    return [
                        'id'        => $seat->id,
                        'seat_code' => $seat->seat_code,
                        'type'      => $seat->type,
                        'price'     => (float) $seat->price,
                        'status'    => $seat->status,
                    ];
                });
        }

        // Lấy combo
        $combos = collect();
        if (!empty($comboIds)) {
            $combos = Product::whereIn('id', $comboIds)
                ->where('is_active', true)
                ->where('stock', '>', 0)
                ->get()
                ->map(function ($combo) {
                    return [
                        'id'    => $combo->id,
                        'name'  => $combo->name,
                        'price' => (float) $combo->price,
                        'description' => $combo->description,
                        'image' => $combo->image ? url($combo->image) : null,
                        'stock' => $combo->stock,
                    ];
                });
        }

        // Tổng tiền
        $totalPrice = $seats->sum(fn($s) => $s['price']) +
            $combos->sum(fn($c) => $c['price']);

        // ==========================
        // ⭐ LOGIC MÃ GIẢM GIÁ CHUẨN THEO DATABASE
        // ==========================
        $promotion = null;
        $discount = 0;

        if ($promotionCode) {
            $promotion = Promotion::where('code', $promotionCode)
                ->where('status', 'active')
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())
                ->first();

            if ($promotion) {

                // 1. Kiểm tra đơn tối thiểu
                if ($promotion->min_order_amount && $totalPrice < $promotion->min_order_amount) {
                    $promotion = null; // không hợp lệ → bỏ mã
                } else {

                    // 2. Tính giảm giá
                    if ($promotion->type === 'percent') {

                        // giảm theo %
                        $discount = $totalPrice * ($promotion->discount_percent / 100);

                        // giới hạn max giảm
                        if (
                            $promotion->max_discount_amount &&
                            $discount > $promotion->max_discount_amount
                        ) {
                            $discount = $promotion->max_discount_amount;
                        }
                    } elseif ($promotion->type === 'money') {

                        // giảm thẳng tiền
                        $discount = $promotion->discount_amount;
                    }

                    // 3. Không vượt quá tổng tiền
                    $discount = min($discount, $totalPrice);
                }
            }
        }

        // Thành tiền cuối cùng
        $finalAmount = $totalPrice - $discount;

        return [
            'success' => true,
            'data' => [
                'showtime' => [
                    'id'         => $showtime->id,
                    'movie'      => [
                        'id'    => $showtime->movie->id ?? null,
                        'title' => $showtime->movie->title ?? null,
                    ],
                    'room' => [
                        'id'   => $showtime->room->id ?? null,
                        'name' => $showtime->room->name ?? null,
                        'cinema' => [
                            'id'   => $showtime->room->cinema->id ?? null,
                            'name' => $showtime->room->cinema->name ?? null,
                        ],
                    ],
                    'show_date' => $showtime->show_date,
                    'show_time' => date('H:i', strtotime($showtime->show_time)),
                ],

                'seats'         => $seats,
                'combos'        => $combos,

                // Giá
                'total_price'   => $totalPrice,
                'discount'      => $discount,
                'final_amount'  => $finalAmount,

                // Mã giảm giá nếu hợp lệ
                'promotion'     => $promotion,
            ],
        ];
    }
}
