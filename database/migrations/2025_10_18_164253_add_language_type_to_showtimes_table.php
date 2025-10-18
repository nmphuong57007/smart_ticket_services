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
        Schema::table('showtimes', function (Blueprint $table) {
            $table->enum('language_type', ['sub', 'dub', 'narrated'])
                ->default('sub')
                ->after('format')
                ->comment('sub: phụ đề, dub: lồng tiếng, narrated: thuyết minh');
        });
    }

    public function down(): void
    {
        Schema::table('showtimes', function (Blueprint $table) {
            $table->dropColumn('language_type');
        });
    }
};
