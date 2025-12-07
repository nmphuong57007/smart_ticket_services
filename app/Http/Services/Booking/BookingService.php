<?php

namespace App\Http\Services\Booking;

use App\Models\Booking;
use App\Models\Seat;
use App\Models\Product;
use App\Models\BookingProduct;
use App\Models\BookingSeat;
use App\Models\Showtime;
use App\Http\Services\Promotion\PromotionService;
use App\Http\Services\Seat\SeatService;
use Illuminate\Support\Facades\DB;
use Exception;

class BookingService
{
    protected SeatService $seatService;

    public function __construct(SeatService $seatService)
    {
        $this->seatService = $seatService;
    }

    /**
     * Tạo đơn giữ chỗ – bước 1 của đặt vé
     */
    public function createBooking(array $data, int $userId)
    {
        return DB::transaction(function () use ($data, $userId) {

            $showtimeId   = $data['showtime_id'];
            $seatIds      = $data['seats'] ?? [];
            $products     = $data['products'] ?? [];
            $discountCode = $data['discount_code'] ?? null;

            // 1. Kiểm tra suất chiếu hợp lệ
            $showtime = Showtime::find($showtimeId);
            if (!$showtime) {
                throw new Exception("Suất chiếu không tồn tại.");
            }

            // 2. Lấy danh sách ghế và khóa
            $seats = Seat::whereIn('id', $seatIds)
                ->where('showtime_id', $showtimeId)
                ->lockForUpdate()
                ->get();

            if ($seats->count() !== count($seatIds)) {
                throw new Exception("Một hoặc nhiều ghế không tồn tại.");
            }

            foreach ($seats as $seat) {
                if ($seat->status !== Seat::STATUS_AVAILABLE) {
                    throw new Exception("Ghế {$seat->seat_code} không khả dụng.");
                }
            }

            // 3. Tính tiền ghế
            $totalSeatPrice = $seats->sum('price');

            // 4. Tính tiền combo
            $totalProductPrice = 0;
            foreach ($products as $item) {
                $product = Product::find($item['product_id']);
                if (!$product) {
                    throw new Exception("Sản phẩm không tồn tại.");
                }
                $totalProductPrice += $product->price * $item['qty'];
            }

            $subTotal = $totalSeatPrice + $totalProductPrice;

            // 5. Áp dụng mã giảm giá
            $discountAmount = 0;

            if ($discountCode) {
                $promotionService = new PromotionService();

                $result = $promotionService->apply(
                    $discountCode,
                    $showtime->movie_id,
                    $subTotal
                );

                if (!$result['valid']) {
                    throw new Exception($result['message']);
                }

                $discountAmount = $result['discount_value'];
            }

            $finalAmount = $subTotal - $discountAmount;

            // 6. Tạo booking (chưa tạo ticket)
            $booking = Booking::create([
                'user_id'        => $userId,
                'showtime_id'    => $showtimeId,
                'discount_code'  => $discountCode,
                'total_amount'   => $subTotal,
                'discount'       => $discountAmount,
                'final_amount'   => $finalAmount,
                'payment_status' => Booking::PAYMENT_PENDING,
                'booking_status' => Booking::BOOKING_PENDING,
                'booking_code'   => 'BK' . time() . rand(100, 999),
            ]);

            // 7. Giữ ghế pending_payment
            $this->seatService->holdSeats($seatIds);

            // 8. Lưu danh sách ghế vào bảng booking_seats
            foreach ($seatIds as $seatId) {
                BookingSeat::create([
                    'booking_id' => $booking->id,
                    'seat_id'    => $seatId,
                ]);
            }

            // 9. Lưu sản phẩm đi kèm
            foreach ($products as $item) {
                BookingProduct::create([
                    'booking_id' => $booking->id,
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['qty'],
                ]);
            }

            // 10. Trả về booking + quan hệ đầy đủ FE cần
            return $booking->load([
                'bookingSeats.seat',
                'products.product',
                'showtime.movie',
                'showtime.room.cinema'
            ]);
        });
    }
}
