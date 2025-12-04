<?php

namespace App\Http\Services\Booking;

use App\Models\Booking;
use App\Models\Seat;
use App\Models\Ticket;
use App\Models\Product;
use App\Models\BookingProduct;
use App\Models\Promotion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class BookingService
{
    /**
     * Tạo booking mới
     */
    public function createBooking(array $data, int $userId)
    {
        return DB::transaction(function () use ($data, $userId) {

            $showtimeId = $data['showtime_id'];
            $seatIds = $data['seats'] ?? [];
            $products = $data['products'] ?? [];
            $discountCode = $data['discount_code'] ?? null;

            //---------------------------------------------------
            // 1. Lấy danh sách ghế và kiểm tra hợp lệ
            //---------------------------------------------------
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

            //---------------------------------------------------
            // 2. Tính tổng tiền ghế
            //---------------------------------------------------
            $totalSeatPrice = $seats->sum('price');

            //---------------------------------------------------
            // 3. Tính tiền sản phẩm (nếu có)
            //---------------------------------------------------
            $totalProductPrice = 0;

            foreach ($products as $item) {
                $p = Product::find($item['product_id']);
                if (!$p) throw new Exception("Sản phẩm không tồn tại.");

                $totalProductPrice += $p->price * $item['qty'];
            }

            //---------------------------------------------------
            // 4. Giảm giá (nếu có)
            //---------------------------------------------------
            $discountAmount = 0;

            if (!empty($discountCode)) {

                // Lấy thông tin mã giảm giá
                $promo = Promotion::where('code', $discountCode)
                    ->where('status', 'active')
                    ->where('start_date', '<=', now()->toDateString())
                    ->where('end_date', '>=', now()->toDateString())
                    ->first();

                if (!$promo) {
                    throw new Exception("Mã giảm giá không hợp lệ hoặc đã hết hạn.");
                }

                //---------------------------------------------------
                // Cách tính giảm giá
                //---------------------------------------------------
                $subTotal = $totalSeatPrice + $totalProductPrice;
                $discountPercent = $promo->discount_percent;
                $discountAmount = $subTotal * ($discountPercent / 100);

                // Không cho giảm quá tổng tiền
                if ($discountAmount > $subTotal) {
                    $discountAmount = $subTotal;
                }
            }
            //---------------------------------------------------
            // 5. Tạo booking
            //---------------------------------------------------
            $finalAmount = $totalSeatPrice + $totalProductPrice - $discountAmount;

            $booking = Booking::create([
                'user_id'        => $userId,
                'showtime_id'    => $showtimeId,
                'discount_code'  => $discountCode,
                'total_amount'   => $totalSeatPrice + $totalProductPrice,
                'discount'       => $discountAmount,
                'final_amount'   => $finalAmount,
                'payment_status' => Booking::STATUS_PENDING,
                'booking_code'   => 'BK' . time() . rand(100, 999),

            ]);

            //---------------------------------------------------
            // 6. Tạo tickets + cập nhật ghế
            //---------------------------------------------------
            foreach ($seats as $seat) {

                // Sinh QR code (chuẩn cho check-in)
                $qrCode = 'TICKET-' . strtoupper(Str::random(10));

                Ticket::create([
                    'booking_id' => $booking->id,
                    'seat_id'    => $seat->id,
                    'qr_code'    => $qrCode,
                ]);

                // update status ghế
                $seat->update(['status' => 'booked']);
            }

            //---------------------------------------------------
            // 7. Lưu sản phẩm đi kèm (nếu có)
            //---------------------------------------------------
            foreach ($products as $item) {
                BookingProduct::create([
                    'booking_id' => $booking->id,
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['qty']
                ]);
            }

            //---------------------------------------------------
            // 8. RETURN booking đầy đủ cho FE
            //---------------------------------------------------
             return $booking->load(['tickets.seat', 'products.product']);
        });
    }
}
