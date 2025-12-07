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
        Schema::create('booking_seats', function (Blueprint $table) {
            $table->id();

            // khóa ngoại tham chiếu booking
            $table->unsignedBigInteger('booking_id');

            // khóa ngoại tham chiếu seat
            $table->unsignedBigInteger('seat_id');

            // đảm bảo không bị lưu trùng ghế cho một booking
            $table->unique(['booking_id', 'seat_id']);

            // Foreign keys
            $table->foreign('booking_id')
                ->references('id')
                ->on('bookings')
                ->onDelete('cascade');

            $table->foreign('seat_id')
                ->references('id')
                ->on('seats')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_seats');
    }
};
