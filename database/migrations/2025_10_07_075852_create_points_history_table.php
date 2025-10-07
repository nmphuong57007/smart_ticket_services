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
        Schema::create('points_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('points'); // Số điểm thay đổi (+/-)
            $table->integer('balance_before'); // Số điểm trước khi thay đổi
            $table->integer('balance_after'); // Số điểm sau khi thay đổi
            $table->enum('type', ['earned', 'spent', 'refunded', 'bonus', 'penalty']); // Loại giao dịch điểm
            $table->string('source'); // Nguồn cộng điểm: booking, promotion, manual, etc.
            $table->string('reference_type')->nullable(); // Loại tham chiếu: booking, promotion, etc.
            $table->unsignedBigInteger('reference_id')->nullable(); // ID tham chiếu đến bảng liên quan
            $table->string('description'); // Mô tả chi tiết giao dịch điểm
            $table->text('metadata')->nullable(); // Thông tin bổ sung dạng JSON
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null'); // Người thực hiện (admin/staff)
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'source']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('points_history');
    }
};
