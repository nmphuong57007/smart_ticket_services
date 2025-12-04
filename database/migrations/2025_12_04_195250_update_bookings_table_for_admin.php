<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {   // cập nhật bảng bookings để thêm các cột mới phục vụ quản trị
        Schema::table('bookings', function (Blueprint $table) {
            // Thêm cột mã giảm giá
            if (!Schema::hasColumn('bookings', 'discount_code')) {
                $table->string('discount_code')->nullable()->after('showtime_id');
            }
            // Thêm cột trạng thái đặt chỗ
            if (!Schema::hasColumn('bookings', 'booking_status')) {
                $table->enum('booking_status', ['pending', 'confirmed', 'canceled', 'expired', 'refunded'])
                    ->default('pending')
                    ->after('payment_status');
            }
            // Thêm cột phương thức thanh toán
            if (!Schema::hasColumn('bookings', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('booking_status');
            }
        });
    }
};
