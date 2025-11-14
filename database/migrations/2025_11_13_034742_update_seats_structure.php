<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Xoá cinema_id nếu tồn tại
        Schema::table('seats', function (Blueprint $table) {
            if (Schema::hasColumn('seats', 'cinema_id')) {
                $table->dropForeign(['cinema_id']);
                $table->dropColumn('cinema_id');
            }
        });

        // Giữ nguyên type: 'normal' và 'vip'
        DB::statement("ALTER TABLE seats MODIFY type ENUM('normal','vip') DEFAULT 'normal'");

        // Sửa status về trạng thái vật lý
        DB::statement("ALTER TABLE seats MODIFY status ENUM('available', 'maintenance', 'broken', 'disabled') DEFAULT 'available'");
    }

    public function down(): void
    {
        // Khôi phục cinema_id khi rollback
        Schema::table('seats', function (Blueprint $table) {
            $table->unsignedBigInteger('cinema_id')->nullable()->after('room_id');
            $table->foreign('cinema_id')->references('id')->on('cinemas')->onDelete('cascade');
        });

        // Trả enum type về ban đầu
        DB::statement("ALTER TABLE seats MODIFY type ENUM('normal','vip') DEFAULT 'normal'");

        // Trả enum status về ban đầu
        DB::statement("ALTER TABLE seats MODIFY status ENUM('available', 'booked') DEFAULT 'available'");
    }
};
