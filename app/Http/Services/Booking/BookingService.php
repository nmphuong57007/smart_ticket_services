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
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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


            $showtime = Showtime::find($showtimeId);
            if (!$showtime) {
                throw new Exception("Suất chiếu không tồn tại.");
            }


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


            $totalSeatPrice = $seats->sum('price');


            $totalProductPrice = 0;
            foreach ($products as $item) {
                $product = Product::find($item['product_id']);
                if (!$product) {
                    throw new Exception("Sản phẩm không tồn tại.");
                }
                $totalProductPrice += $product->price * $item['qty'];
            }

            $subTotal = $totalSeatPrice + $totalProductPrice;


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


            $this->seatService->holdSeats($seatIds);

            foreach ($seats as $seat) {
                BookingSeat::create([
                    'booking_id' => $booking->id,
                    'seat_id'    => $seat->id,

                    // LƯU GIÁ GHẾ TẠI THỜI ĐIỂM BOOKING
                    'price'      => $seat->price,
                ]);
            }


            foreach ($products as $item) {
                BookingProduct::create([
                    'booking_id' => $booking->id,
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['qty'],
                ]);
            }


            return $booking->load([
                'bookingSeats.seat',
                'products.product',
                'showtime.movie',
                'showtime.room.cinema'
            ]);
        });
    }

    /**
     * ADMIN / STAFF – phân trang + filter booking
     */
    public function paginateBookings(array $filters = []): LengthAwarePaginator
    {
        $query = Booking::with([
            'user',
            'payments',
            'ticket',
            'bookingSeats.seat',
            'showtime.movie',
            'showtime.room.cinema',
        ]);

        // LỌC THEO MÃ ĐƠN VÉ ID
        if (!empty($filters['booking_id'])) {
            $query->where('id', $filters['booking_id']);
        }

        // LỌC THEO MÃ ĐƠN VÉ
        if (!empty($filters['booking_code'])) {
            $query->where('booking_code', 'like', '%' . $filters['booking_code'] . '%');
        }

        // LỌC THEO QR CODE
        if (!empty($filters['qr_code'])) {
            try {
                $json = base64_decode($filters['qr_code'], true);
                $data = json_decode($json, true);

                if (is_array($data) && isset($data['booking_id'])) {
                    $query->where('id', $data['booking_id']);
                } else {
                    $query->whereRaw('1 = 0');
                }
            } catch (\Throwable $e) {
                $query->whereRaw('1 = 0');
            }
        }

        // LỌC THEO TÊN NGƯỜI DÙNG
        if (!empty($filters['user_name'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('fullname', 'like', '%' . $filters['user_name'] . '%');
            });
        }

        // LỌC THEO TRẠNG THÁI
        if (!empty($filters['status'])) {
            $query->where('booking_status', $filters['status']);
        }

        // SẮP XẾP
        $sortBy    = $filters['sort_by'] ?? 'id';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        // Chỉ cho phép sắp xếp theo các cột an toàn
        $allowedSorts = ['id', 'created_at', 'final_amount'];

        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        // Chuẩn hóa sort order
        $sortOrder = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';

        // PHÂN TRANG
        return $query
            ->orderBy($sortBy, $sortOrder)
            ->paginate($filters['per_page'] ?? 15);
    }
}
