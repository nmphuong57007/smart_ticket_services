<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seat_types', function (Blueprint $table) {
            $table->id();
            $table->enum('name', ['Ghế thường', 'Ghế Vip', 'Ghế đôi']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seat_types');
    }
};
