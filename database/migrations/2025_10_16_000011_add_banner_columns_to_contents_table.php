<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            if (!Schema::hasColumn('contents', 'start_date')) {
                $table->dateTime('start_date')->nullable()->after('description');
            }
            if (!Schema::hasColumn('contents', 'end_date')) {
                $table->dateTime('end_date')->nullable()->after('start_date');
            }
            if (!Schema::hasColumn('contents', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('end_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date', 'is_active']);
        });
    }
};
