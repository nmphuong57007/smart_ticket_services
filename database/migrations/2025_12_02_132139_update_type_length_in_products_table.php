<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('type', 20)->change(); // đủ chứa combo, drink, food
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('type', 4)->change(); // hoặc độ dài cũ của bạn
        });
    }
};
