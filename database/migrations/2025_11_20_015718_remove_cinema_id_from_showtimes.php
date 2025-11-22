<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   // Xóa cột cinema_id khỏi bảng showtimes
    public function up()
    {
        Schema::table('showtimes', function (Blueprint $table) {
            if (Schema::hasColumn('showtimes', 'cinema_id')) {
                $table->dropColumn('cinema_id');
            }
        });
    }

    public function down()
    {
        Schema::table('showtimes', function (Blueprint $table) {
            $table->unsignedBigInteger('cinema_id')->nullable();
            $table->index('cinema_id');
        });
    }
};
