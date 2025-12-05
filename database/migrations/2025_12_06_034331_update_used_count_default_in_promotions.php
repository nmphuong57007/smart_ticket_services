<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->unsignedInteger('used_count')
                ->default(0)
                ->nullable(false)
                ->change();
        });
    }

    public function down()
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->unsignedInteger('used_count')
                ->nullable()
                ->default(null)
                ->change();
        });
    }
};
