<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Gỡ foreign key để tránh lỗi
        Schema::table('seat_reservations', function (Blueprint $table) {
            try {
                $table->dropForeign(['showtime_id']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropForeign(['seat_id']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropForeign(['user_id']);
            } catch (\Exception $e) {
            }
        });

        Schema::dropIfExists('seat_reservations');
    }

    public function down(): void
    {
        Schema::create('seat_reservations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('showtime_id');
            $table->unsignedBigInteger('seat_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->enum('status', ['available', 'reserved', 'booked'])->default('available');
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('booked_at')->nullable();
            $table->timestamps();

            $table->unique(['showtime_id', 'seat_id']);

            $table->foreign('showtime_id')->references('id')->on('showtimes')->onDelete('cascade');
            $table->foreign('seat_id')->references('id')->on('seats')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }
};
