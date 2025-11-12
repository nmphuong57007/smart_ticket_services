<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('seat_reservations', function (Blueprint $table) {
            // Thêm 2 cột thời gian giữ ghế và thời gian đặt ghế
            $table->timestamp('reserved_at')->nullable()->after('status');
            $table->timestamp('booked_at')->nullable()->after('reserved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seat_reservations', function (Blueprint $table) {
            // Xóa các cột khi rollback
            $table->dropColumn(['reserved_at', 'booked_at']);
        });
    }
};
