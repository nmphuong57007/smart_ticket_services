<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Booking;
use App\Models\BookingProduct;
use App\Models\Ticket;
use App\Models\Payment;
use App\Models\Seat;
use Illuminate\Support\Str;

class BookingDemoSeeder extends Seeder
{
    public function run()
    {
        $customerId = 3; // user customer trong UsersSeeder
        $showtimeId = 1; // showtime có sẵn từ ShowtimesSeeder
        $productId = 1; // product từ ProductSeeder

        // Lấy tối thiểu 10 ghế để phân chia cho 5 booking
        $seats = Seat::where('showtime_id', $showtimeId)
            ->where('status', 'available')
            ->take(10)
            ->get();

        if ($seats->count() < 10) {
            $this->command->warn("⚠ Không đủ ghế available để seed booking.");
            return;
        }

        $seatChunks = $seats->chunk(2); // mỗi booking có 2 ghế

        for ($i = 0; $i < 5; $i++) {

            $selectedSeats = $seatChunks[$i];

            $totalAmount = 0;

            foreach ($selectedSeats as $seat) {
                $totalAmount += $seat->price;
            }

            // tạo booking
            $booking = Booking::create([
                'user_id' => $customerId,
                'showtime_id' => $showtimeId,
                'discount_code' => null,
                'total_amount' => $totalAmount,
                'discount' => 0,
                'final_amount' => $totalAmount,
                'payment_status' => 'paid',
                'booking_code' => 'BK' . time() . rand(100, 999),
            ]);

            // tickets
            foreach ($selectedSeats as $seat) {
                Ticket::create([
                    'booking_id' => $booking->id,
                    'seat_id' => $seat->id,
                    'qr_code' => 'TICKET-' . strtoupper(Str::random(10)),
                ]);

                // cập nhật ghế: booked
                $seat->update(['status' => 'booked']);
            }

            // sản phẩm (mỗi booking mua 1 combo)
            BookingProduct::create([
                'booking_id' => $booking->id,
                'product_id' => $productId,
                'quantity' => 1,
            ]);

            // payment
            Payment::create([
                'booking_id' => $booking->id,
                'user_id' => $customerId,
                'method' => 'vnpay',
                'amount' => $totalAmount,
                'status' => 'success',
                'transaction_code' => rand(10000000, 99999999),
                'transaction_uuid' => Str::uuid(),
                'bank_code' => 'NCB',
                'pay_url' => 'https://sandbox.vnpayment.vn/paymentv2/',
                'paid_at' => now(),
            ]);
        }

        $this->command->info("🎉 Seed thành công 5 booking mẫu!");
    }
}
