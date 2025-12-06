<?php

namespace App\Http\Services\Booking;

use App\Models\Booking;
use App\Models\Seat;
use App\Models\Ticket;
use App\Models\Product;
use App\Models\BookingProduct;
use App\Models\Showtime;
use App\Http\Services\Promotion\PromotionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class BookingService
{
    public function createBooking(array $data, int $userId)
    {
        return DB::transaction(function () use ($data, $userId) {

            $showtimeId   = $data['showtime_id'];
            $seatIds      = $data['seats'] ?? [];
            $products     = $data['products'] ?? [];
            $discountCode = $data['discount_code'] ?? null;

            // 1. Lấy suất chiếu -> movie_id (KHÔNG dùng room!)
            $showtime = Showtime::find($showtimeId);
            if (!$showtime) {
                throw new Exception("Suất chiếu không tồn tại.");
            }
            $movieId = $showtime->movie_id;

            // 2. Kiểm tra ghế THEO SHOWTIME_ID (đúng nhánh main)
            $seats = Seat::whereIn('id', $seatIds)
                ->where('showtime_id', $showtimeId)
                ->lockForUpdate()
                ->get();

            if (count($seats) !== count($seatIds)) {
                throw new Exception("Một hoặc nhiều ghế không hợp lệ.");
            }

            foreach ($seats as $seat) {
                if ($seat->status === 'booked') {
                    throw new Exception("Ghế {$seat->seat_code} đã được đặt trước.");
                }
            }

            // 3. Tính tiền ghế
            $totalSeatPrice = $seats->sum('price');

            // 4. Tính tiền sản phẩm
            $totalProductPrice = 0;

            foreach ($products as $item) {
                $p = Product::find($item['product_id']);
                if (!$p) throw new Exception("Sản phẩm không tồn tại.");
                $totalProductPrice += $p->price * $item['qty'];
            }

            // Tổng tiền trước giảm
            $subTotal = $totalSeatPrice + $totalProductPrice;

            // 5. Áp dụng mã giảm giá (logic mới nhưng KHÔNG ảnh hưởng phần khác)
            $discountAmount = 0;

            if (!empty($discountCode)) {

                $promotionService = new PromotionService();

                $applyResult = $promotionService->apply(
                    $discountCode,
                    $movieId,
                    $subTotal
                );

                if (!$applyResult['valid']) {
                    throw new Exception($applyResult['message']);
                }

                $discountAmount = $applyResult['discount_value'];
            }

            // 6. Final
            $finalAmount = $subTotal - $discountAmount;

            // 7. Tạo booking
            $booking = Booking::create([
                'user_id'        => $userId,
                'showtime_id'    => $showtimeId,
                'discount_code'  => $discountCode,
                'total_amount'   => $subTotal,
                'discount'       => $discountAmount,
                'final_amount'   => $finalAmount,
                'payment_status' => Booking::STATUS_PENDING,
                'booking_code'   => 'BK' . time() . rand(100, 999),
            ]);

            // 8. Tạo vé
            foreach ($seats as $seat) {
                Ticket::create([
                    'booking_id' => $booking->id,
                    'seat_id'    => $seat->id,
                    'qr_code'    => 'TICKET-' . strtoupper(Str::random(10)),
                ]);

                $seat->update(['status' => 'booked']);
            }

            // 9. Lưu sản phẩm
            foreach ($products as $item) {
                BookingProduct::create([
                    'booking_id' => $booking->id,
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['qty']
                ]);
            }

            return $booking->load(['tickets.seat', 'products.product']);
        });
    }
}
