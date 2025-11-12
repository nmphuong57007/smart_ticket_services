<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cinemas', function (Blueprint $table) {
            // Chỉ thêm updated_at nếu chưa có
            if (!Schema::hasColumn('cinemas', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cinemas', function (Blueprint $table) {
            if (Schema::hasColumn('cinemas', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }
};
