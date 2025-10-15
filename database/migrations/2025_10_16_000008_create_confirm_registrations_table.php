<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('confirm_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('email', 100);
            $table->string('verification_code', 100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('confirm_registrations');
    }
};
