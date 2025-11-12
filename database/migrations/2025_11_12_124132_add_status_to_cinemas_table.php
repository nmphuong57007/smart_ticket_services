<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cinemas', function (Blueprint $table) {
            if (!Schema::hasColumn('cinemas', 'status')) {
                $table->enum('status', ['active', 'inactive'])
                    ->default('active')
                    ->after('phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cinemas', function (Blueprint $table) {
            if (Schema::hasColumn('cinemas', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
