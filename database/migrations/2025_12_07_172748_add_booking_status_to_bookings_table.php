<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add booking_status to bookings table.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Thêm trạng thái đơn hàng
            $table->string('booking_status')
                ->default('pending')
                ->after('payment_status'); 
        });
    }

    /**
     * Rollback.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('booking_status');
        });
    }
};
