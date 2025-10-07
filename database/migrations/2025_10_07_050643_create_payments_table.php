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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->enum('method', ['momo', 'zalopay', 'vnpay', 'card', 'qr'])->default('momo');
            $table->decimal('amount', 10, 2)->nullable();
            $table->enum('status', ['success', 'failed', 'pending', 'refunded'])->default('pending');
            $table->string('transaction_code', 100)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            
            $table->index('booking_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
