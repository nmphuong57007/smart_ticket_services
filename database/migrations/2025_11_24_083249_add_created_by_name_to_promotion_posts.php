<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('promotion_posts', function (Blueprint $table) {
            $table->string('created_by_name', 191)
                ->nullable()
                ->after('created_by'); // đặt sau created_by (user_id)
        });
    }

    public function down()
    {
        Schema::table('promotion_posts', function (Blueprint $table) {
            $table->dropColumn('created_by_name');
        });
    }
};
