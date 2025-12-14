<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        //  1. Lấy danh sách foreign key của bảng tickets
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'tickets'
              AND COLUMN_NAME = 'seat_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        //  2. Drop FK nếu tồn tại
        foreach ($foreignKeys as $fk) {
            DB::statement("ALTER TABLE tickets DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
        }

        Schema::table('tickets', function (Blueprint $table) {

            //  3. Drop index seat_id nếu tồn tại
            if (Schema::hasColumn('tickets', 'seat_id')) {
                try {
                    $table->dropIndex(['seat_id']);
                } catch (\Throwable $e) {}
            }

            //  4. Drop column seat_id
            if (Schema::hasColumn('tickets', 'seat_id')) {
                $table->dropColumn('seat_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('seat_id')->nullable()->after('booking_id');
            $table->index('seat_id');
            $table->foreign('seat_id')->references('id')->on('seats');
        });
    }
};
