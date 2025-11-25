<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('content_posts', function (Blueprint $table) {
            $table->id();

            // Phân loại nội dung
            $table->enum('type', ['banner', 'news', 'promotion'])->default('news');

            $table->string('title');
            $table->string('short_description')->nullable();
            $table->text('description')->nullable();

            $table->string('slug')->unique();
            $table->string('image')->nullable(); // ảnh banner/thumbnail

            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('content_posts');
    }
};
