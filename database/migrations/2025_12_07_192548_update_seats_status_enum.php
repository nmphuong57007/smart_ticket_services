<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Thêm giá trị pending_payment vào enum status
        DB::statement("
            ALTER TABLE seats
            MODIFY status ENUM('available', 'selected', 'booked', 'pending_payment')
            DEFAULT 'available'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback về enum cũ nếu cần
        DB::statement("
            ALTER TABLE seats
            MODIFY status ENUM('available', 'selected', 'booked')
            DEFAULT 'available'
        ");
    }
};
