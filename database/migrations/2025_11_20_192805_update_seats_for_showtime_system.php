<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('seats', function (Blueprint $table) {

            // 1. Xóa khóa ngoại cũ
            if (Schema::hasColumn('seats', 'room_id')) {
                $table->dropForeign(['room_id']);
                $table->dropColumn('room_id');
            }

            // 2. Thêm showtime_id
            $table->unsignedBigInteger('showtime_id')->after('id');

            // 3. FK mới
            $table->foreign('showtime_id')
                ->references('id')
                ->on('showtimes')
                ->onDelete('cascade');

            // 4. Unique seat_code theo suất chiếu
            $table->unique(['showtime_id', 'seat_code']);
        });

        // 5. Sửa ENUM status (dùng cho đặt vé)
        DB::statement("ALTER TABLE seats MODIFY status ENUM('available','selected','booked') DEFAULT 'available'");
    }
};
