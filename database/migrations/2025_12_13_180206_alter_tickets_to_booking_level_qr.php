<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {

            // 1 booking = 1 ticket
            $table->unique('booking_id');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {

            // rollback unique
            $table->dropUnique(['booking_id']);
        });
    }
};
