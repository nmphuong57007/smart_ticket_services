<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // thêm cột unpublished_at vào bảng content_posts để theo dõi thời gian hủy xuất bản bài viết
    public function up()
    {
        Schema::table('content_posts', function (Blueprint $table) {
            $table->timestamp('unpublished_at')->nullable()->after('published_at');
        });
    }

    public function down()
    {
        Schema::table('content_posts', function (Blueprint $table) {
            $table->dropColumn('unpublished_at');
        });
    }
};
