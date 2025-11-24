<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('promotion_posts', function (Blueprint $table) {
            // Xóa cột target_url
            if (Schema::hasColumn('promotion_posts', 'target_url')) {
                $table->dropColumn('target_url');
            }

            // Đổi kiểu created_by -> varchar(191)
            $table->string('created_by', 191)->change();
        });
    }

    public function down()
    {
        Schema::table('promotion_posts', function (Blueprint $table) {
            // Khôi phục lại cột target_url
            $table->string('target_url', 191)->nullable();

            // Khôi phục created_by về bigint
            $table->unsignedBigInteger('created_by')->change();
        });
    }
};
