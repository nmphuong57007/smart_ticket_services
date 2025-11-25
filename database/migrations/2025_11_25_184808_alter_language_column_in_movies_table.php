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
        Schema::table('movies', function (Blueprint $table) {
            $table->string('language', 50)->change();
        });
    }

    public function down()
    {
        Schema::table('movies', function (Blueprint $table) {
            // quay về kiểu cũ (tùy)
            $table->string('language', 10)->change();
        });
    }
};
