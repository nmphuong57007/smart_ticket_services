<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Booking;
use App\Models\BookingProduct;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\Product;
use App\Models\Ticket;
use App\Models\Discount;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Bạn cần đăng nhập để đặt vé.'
            ], 401);
        }

        $validated = $request->validate([
            'showtime_id' => 'required|exists:showtimes,id',
            'seats' => 'required|array|min:1',
            'seats.*' => 'integer|exists:seats,id',
            'products' => 'nullable|array',
            'products.*.id' => 'required|integer|exists:products,id',
            'products.*.quantity' => 'integer|min:1',
            'discount_code' => 'nullable|string'
        ]);

        $seatIds = collect($validated['seats'])->map(fn ($id) => (int) $id)->values();

        if ($seatIds->unique()->count() !== $seatIds->count()) {
            return response()->json([
                'message' => 'Danh sách ghế không hợp lệ (trùng ghế).'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $showtime = Showtime::lockForUpdate()->find($validated['showtime_id']);

            if (!$showtime) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Không tìm thấy suất chiếu.'
                ], 404);
            }

            $showDate = $showtime->show_date;
            $showTime = $showtime->show_time;
            if ($showDate && $showTime) {
                $showDateTime = Carbon::parse($showDate . ' ' . $showTime);
                if ($showDateTime->isPast()) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Suất chiếu này đã kết thúc, không thể đặt vé.'
                    ], 422);
                }
            }

            // 1️⃣ Kiểm tra ghế còn trống
            $selectedSeats = Seat::whereIn('id', $seatIds)
                ->where('status', 'available')
                ->where('showtime_id', $showtime->id)
                ->lockForUpdate()
                ->get();

            if ($selectedSeats->count() !== $seatIds->count()) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Một hoặc nhiều ghế đã được đặt. Vui lòng chọn lại.'
                ], 409);
            }

            // 2️⃣ Tính tiền vé
            $ticketPrice = $selectedSeats->sum(fn ($seat) => $seat->price ?? 0);
            if ($ticketPrice <= 0) {
                $ticketPrice = (float) $showtime->price * $selectedSeats->count();
            }

            $productTotal = 0;
            $productItems = [];

            if (!empty($validated['products'])) {
                $productPayload = collect($validated['products']);
                $products = Product::whereIn('id', $productPayload->pluck('id'))
                    ->get()
                    ->keyBy('id');

                foreach ($productPayload as $productRequest) {
                    $product = $products->get($productRequest['id']);
                    if (!$product) {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'Sản phẩm không tồn tại.'
                        ], 404);
                    }

                    $quantity = (int) ($productRequest['quantity'] ?? 1);
                    if ($quantity < 1) {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'Số lượng sản phẩm phải lớn hơn 0.'
                        ], 422);
                    }
                    $lineTotal = (float) $product->price * $quantity;
                    $productTotal += $lineTotal;

                    $productItems[] = [
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                    ];
                }
            }

            $discountValue = 0;
            if (!empty($validated['discount_code'])) {
                $discount = Discount::where('code', $validated['discount_code'])->first();

                if (!$discount) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Mã giảm giá không tồn tại.'
                    ], 404);
                }

                if (!$discount->isValid()) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Mã giảm giá đã hết hạn hoặc không khả dụng.'
                    ], 422);
                }

                $discountValue = (($ticketPrice + $productTotal) * (float) $discount->discount_percent) / 100;
            }

            $total = round($ticketPrice + $productTotal, 2);
            $discountValue = round(min($discountValue, $total), 2);
            $final = round(max($total - $discountValue, 0), 2);

            // 3️⃣ Tạo booking
            $booking = Booking::create([
                'user_id' => $user->id,
                'showtime_id' => $showtime->id,
                'discount_code' => $validated['discount_code'] ?? null,
                'total_amount' => $total,
                'discount' => $discountValue,
                'final_amount' => $final,
                'payment_status' => 'paid', // giả định đã thanh toán
            ]);

            // 4️⃣ Tạo booking_products
            foreach ($productItems as $item) {
                BookingProduct::create([
                    'booking_id' => $booking->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            // 5️⃣ Đánh dấu ghế đã đặt + tạo ticket
            foreach ($selectedSeats as $seat) {
                $seat->update(['status' => 'booked']);
                Ticket::create([
                    'booking_id' => $booking->id,
                    'seat_id' => $seat->id,
                    'qr_code' => 'TICKET-' . Str::uuid()->toString(),
                ]);
            }

            // 6️⃣ Nếu phòng hết chỗ, báo "hết vé"
            $remainingSeats = Seat::where('showtime_id', $showtime->id)
                ->where('status', 'available')
                ->count();
            $message = $remainingSeats === 0 ? 'Hết chỗ cho suất chiếu này.' : 'Đặt vé thành công.';

            DB::commit();

            return response()->json([
                'message' => $message,
                'booking_id' => $booking->id,
                'total_amount' => $total,
                'discount_amount' => $discountValue,
                'final_amount' => $final,
                'tickets' => $booking->tickets()->with('seat')->get(),
                'products' => $booking->bookingProducts()->with('product')->get(),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
