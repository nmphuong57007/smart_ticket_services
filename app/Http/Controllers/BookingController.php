<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Booking;
use App\Models\BookingProduct;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\Product;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'showtime_id' => 'required|exists:showtimes,id',
            'seats' => 'required|array|min:1',
            'seats.*' => 'exists:seats,id',
            'products' => 'array',
            'products.*.id' => 'exists:products,id',
            'products.*.quantity' => 'integer|min:1',
            'discount_code' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $showtime = Showtime::find($validated['showtime_id']);

            // 1️⃣ Kiểm tra ghế còn trống
            $selectedSeats = Seat::whereIn('id', $validated['seats'])
                ->where('status', 'available')
                ->where('showtime_id', $showtime->id)
                ->lockForUpdate()
                ->get();

            if ($selectedSeats->count() != count($validated['seats'])) {
                return response()->json([
                    'message' => 'Một hoặc nhiều ghế đã được đặt. Vui lòng chọn lại.'
                ], 400);
            }

            // 2️⃣ Tính tiền vé
            $ticketPrice = $showtime->price * count($validated['seats']);
            $productTotal = 0;

            if (!empty($validated['products'])) {
                foreach ($validated['products'] as $p) {
                    $product = Product::find($p['id']);
                    $productTotal += $product->price * $p['quantity'];
                }
            }

            $discount = 0;
            if (!empty($validated['discount_code'])) {
                // Có thể thêm bảng discount riêng — ở đây chỉ demo
                if ($validated['discount_code'] === 'SALE10') {
                    $discount = ($ticketPrice + $productTotal) * 0.1;
                }
            }

            $total = $ticketPrice + $productTotal;
            $final = $total - $discount;

            // 3️⃣ Tạo booking
            $booking = Booking::create([
                'user_id' => $user->id,
                'showtime_id' => $showtime->id,
                'discount_code' => $validated['discount_code'] ?? null,
                'total_amount' => $total,
                'discount' => $discount,
                'final_amount' => $final,
                'payment_status' => 'paid', // giả định đã thanh toán
            ]);

            // 4️⃣ Tạo booking_products
            if (!empty($validated['products'])) {
                foreach ($validated['products'] as $p) {
                    BookingProduct::create([
                        'booking_id' => $booking->id,
                        'product_id' => $p['id'],
                        'quantity' => $p['quantity'],
                    ]);
                }
            }

            // 5️⃣ Đánh dấu ghế đã đặt + tạo ticket
            foreach ($selectedSeats as $seat) {
                $seat->update(['status' => 'booked']);
                Ticket::create([
                    'booking_id' => $booking->id,
                    'seat_id' => $seat->id,
                    'qr_code' => 'TICKET-' . uniqid(),
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
                'final_amount' => $final,
                'tickets' => $booking->tickets()->get(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
