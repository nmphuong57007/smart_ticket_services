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
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('user_id');

            $table->enum('method', ['vnpay', 'banking', 'momo', 'zalopay', 'card', 'qr']);

            $table->decimal('amount', 12, 2);

            $table->enum('status', ['pending', 'success', 'failed', 'refunded'])->default('pending');

            $table->string('transaction_code')->nullable();   // VNPAY transaction no
            $table->string('transaction_uuid')->nullable();   // mã hệ thống của bạn
            $table->string('bank_code')->nullable();          // Mã ngân hàng khách chọn
            $table->text('pay_url')->nullable();              // Link thanh toán

            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
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
