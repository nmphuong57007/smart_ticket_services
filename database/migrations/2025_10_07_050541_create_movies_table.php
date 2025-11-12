<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('poster')->nullable();
            $table->string('trailer')->nullable();
            $table->text('description')->nullable();
            $table->string('genre', 100)->nullable();
            $table->integer('duration')->nullable();
            $table->string('format', 50)->nullable();
            $table->date('release_date')->nullable();
            $table->enum('status', ['coming', 'showing', 'stopped'])->default('coming');
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
