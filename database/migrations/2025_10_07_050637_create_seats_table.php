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
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('showtime_id');
            $table->string('seat_code', 10);
            $table->enum('type', ['normal', 'vip'])->default('normal');
            $table->enum('status', ['available', 'booked'])->default('available');
            $table->decimal('price', 10, 2)->default(0);
            $table->index('showtime_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
