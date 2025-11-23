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
        Schema::create('promotion_posts', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('title', 255)->comment('Tiêu đề bài viết/banner');
            $table->text('description')->nullable()->comment('Mô tả ngắn/Nội dung chi tiết');
            $table->string('slug', 191)->unique()->comment('URL thân thiện');

            $table->string('image_url', 191)->nullable()->comment('Đường dẫn ảnh banner/thumbnail');
            $table->string('target_url', 191)->nullable()->comment('Link liên kết khi click (vd: tới trang đặt vé)');

            $table->timestamp('published_at')->nullable()->comment('Thời gian bài viết được đăng');
            $table->boolean('is_published')->default(false)->comment('Trạng thái hiển thị');

            $table->unsignedBigInteger('created_by')->nullable()->comment('Tác giả');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_posts');
    }
};
