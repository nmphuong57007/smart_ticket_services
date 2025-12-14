<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {

            // ❌ XÓA HOÀN TOÀN seat_id
            if (Schema::hasColumn('tickets', 'seat_id')) {
                $table->dropColumn('seat_id');
            }

            // ✅ 1 booking = 1 ticket
            $table->unique('booking_id');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {

            // 🔙 rollback: thêm lại seat_id (nullable)
            $table->unsignedBigInteger('seat_id')->nullable()->after('booking_id');

            // 🔙 bỏ unique booking_id
            $table->dropUnique(['booking_id']);
        });
    }
};
