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
        // Add foreign key constraints
        Schema::table('rooms', function (Blueprint $table) {
            $table->foreign('cinema_id')->references('id')->on('cinemas')->onDelete('cascade');
        });

        Schema::table('showtimes', function (Blueprint $table) {
            $table->foreign('movie_id')->references('id')->on('movies')->onDelete('cascade');
            $table->foreign('room_id')->references('id')->on('rooms')->onDelete('cascade');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('showtime_id')->references('id')->on('showtimes');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('movie_id')->references('id')->on('movies');
        });

        Schema::table('seats', function (Blueprint $table) {
            // $table->foreign('room_id')->references('id')->on('rooms')->onDelete('cascade');
        });


        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('booking_id')->references('id')->on('bookings');
            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->foreign('seat_id')->references('id')->on('seats');
        });

        Schema::table('booking_products', function (Blueprint $table) {
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraints
        Schema::table('booking_products', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropForeign(['product_id']);
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropForeign(['seat_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropForeign(['user_id']);
        });

        // Schema::table('seats', function (Blueprint $table) {
        //     $table->dropForeign(['room_id']);
        // });


        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['movie_id']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['showtime_id']);
        });

        Schema::table('showtimes', function (Blueprint $table) {
            $table->dropForeign(['movie_id']);
            $table->dropForeign(['room_id']);
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropForeign(['cinema_id']);
        });
    }
};
