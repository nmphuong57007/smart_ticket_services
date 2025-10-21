<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Nếu muốn phân biệt snack đơn lẻ vs combo
            $table->enum('type', ['single', 'combo'])
                ->default('single')
                ->after('name');

            // Bật/tắt hiển thị trên frontend
            $table->boolean('is_active')->default(true)->after('stock');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['type', 'is_active']);
        });
    }
};
