<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('showtimes', function (Blueprint $table) {

            // Xóa foreign key cũ (CASCADE)
            $table->dropForeign(['movie_id']);

            // Tạo lại foreign key chỉ RESTRICT (không cho xóa)
            $table->foreign('movie_id')
                ->references('id')
                ->on('movies')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('showtimes', function (Blueprint $table) {

            // Xóa foreign key restrict
            $table->dropForeign(['movie_id']);

            // Restore lại CASCADE nếu rollback
            $table->foreign('movie_id')
                ->references('id')
                ->on('movies')
                ->onDelete('cascade');
        });
    }
};
