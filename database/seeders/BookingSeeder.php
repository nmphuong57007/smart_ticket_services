<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Booking;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        // Booking 1 - đã thanh toán
        Booking::create([
            'user_id'        => 1,
            'showtime_id'    => 1,
            'discount_code'  => null,
            'total_amount'   => 120000,
            'discount'       => 0,
            'final_amount'   => 120000,
            'payment_status' => 'paid',
            'booking_status' => 'confirmed',
            'payment_method' => 'vnpay',
        ]);

        // Booking 2 - đang chờ thanh toán
        Booking::create([
            'user_id'        => 2,
            'showtime_id'    => 1,
            'discount_code'  => 'SALE20',
            'total_amount'   => 200000,
            'discount'       => 40000,
            'final_amount'   => 160000,
            'payment_status' => 'pending',
            'booking_status' => 'pending',
            'payment_method' => 'vnpay',
        ]);

        // Booking 3 - thanh toán thất bại
        Booking::create([
            'user_id'        => 1,
            'showtime_id'    => 1,
            'discount_code'  => null,
            'total_amount'   => 150000,
            'discount'       => 0,
            'final_amount'   => 150000,
            'payment_status' => 'failed',
            'booking_status' => 'pending',
            'payment_method' => 'vnpay',
        ]);

        // Booking 4 - đã hoàn tiền
        Booking::create([
            'user_id'        => 3,
            'showtime_id'    => 1,
            'discount_code'  => 'SALE50',
            'total_amount'   => 200000,
            'discount'       => 100000,
            'final_amount'   => 100000,
            'payment_status' => 'refunded',
            'booking_status' => 'refunded',
            'payment_method' => 'vnpay',
        ]);

        // Booking 5 - admin hủy đơn
        Booking::create([
            'user_id'        => 1,
            'showtime_id'    => 1,
            'discount_code'  => null,
            'total_amount'   => 180000,
            'discount'       => 0,
            'final_amount'   => 180000,
            'payment_status' => 'pending',
            'booking_status' => 'canceled',
            'payment_method' => 'vnpay',
        ]);
    }
}
