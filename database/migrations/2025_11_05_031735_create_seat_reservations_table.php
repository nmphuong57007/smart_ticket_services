<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seat_reservations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('showtime_id');
            $table->unsignedBigInteger('seat_id');
            $table->unsignedBigInteger('user_id')->nullable(); // ai đặt ghế này
            $table->enum('status', ['available', 'reserved', 'booked'])->default('available');
            $table->timestamps();

            $table->unique(['showtime_id', 'seat_id']); // Mỗi suất chỉ có 1 bản ghi / ghế
            $table->foreign('showtime_id')->references('id')->on('showtimes')->onDelete('cascade');
            $table->foreign('seat_id')->references('id')->on('seats')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seat_reservations');
    }
};
