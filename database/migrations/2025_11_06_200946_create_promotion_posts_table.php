<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255)->comment('Tiêu đề bài viết/banner');
            $table->text('description')->nullable()->comment('Mô tả ngắn/Nội dung chi tiết');
            $table->string('slug')->unique()->comment('URL thân thiện');

            $table->string('image_url')->nullable()->comment('Đường dẫn ảnh banner/thumbnail');
            $table->string('target_url')->nullable()->comment('Link liên kết khi click (vd: tới trang đặt vé)');

            $table->timestamp('published_at')->nullable()->comment('Thời gian bài viết được đăng');
            $table->boolean('is_published')->default(false)->comment('Trạng thái hiển thị');

            // Tham chiếu đến mã giảm giá (Tùy chọn)
            // $table->foreignId('promotion_id')->nullable()->constrained('promotions'); 

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_posts');
    }
};
