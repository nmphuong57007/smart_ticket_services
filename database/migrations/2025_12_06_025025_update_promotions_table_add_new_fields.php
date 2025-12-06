<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {

            // Kiểu giảm giá: percent hoặc money
            $table->enum('type', ['percent', 'money'])
                  ->default('percent')
                  ->after('code');

            // Số tiền giảm cố định khi type = money
            $table->integer('discount_amount')
                  ->nullable()
                  ->after('discount_percent');

            // Giới hạn mức giảm tối đa khi type = percent
            $table->integer('max_discount_amount')
                  ->nullable()
                  ->after('discount_amount');

            // Tổng số lượt mã được sử dụng
            $table->integer('usage_limit')
                  ->default(0)
                  ->after('max_discount_amount');

            // Số lượt đã sử dụng
            $table->integer('used_count')
                  ->default(0)
                  ->after('usage_limit');

            // Áp dụng mã cho phim nào (nullable = áp dụng cho tất cả phim)
            $table->unsignedBigInteger('movie_id')
                  ->nullable()
                  ->after('used_count');

            // Đơn hàng tối thiểu được sử dụng mã
            $table->integer('min_order_amount')
                  ->nullable()
                  ->after('movie_id');

            // Mở rộng enum status
            $table->enum('status', ['active', 'upcoming', 'expired', 'disabled'])
                  ->default('active')
                  ->change();
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {

            $table->dropColumn([
                'type',
                'discount_amount',
                'max_discount_amount',
                'usage_limit',
                'used_count',
                'movie_id',
                'min_order_amount',
            ]);

            // Rollback status thủ công nếu cần:
            // $table->enum('status', ['active', 'expired'])->default('active')->change();
        });
    }
};
