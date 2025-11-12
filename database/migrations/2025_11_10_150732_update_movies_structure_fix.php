<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            // Xóa cột genre nếu tồn tại
            if (Schema::hasColumn('movies', 'genre')) {
                $table->dropColumn('genre');
            }

            // Thêm cột language nếu chưa có
            if (!Schema::hasColumn('movies', 'language')) {
                $table->enum('language', ['dub', 'sub', 'narrated'])->default('sub')->after('format');
            }

            // Thêm cột end_date nếu chưa có
            if (!Schema::hasColumn('movies', 'end_date')) {
                $table->date('end_date')->nullable()->after('release_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            // Khôi phục cột genre nếu rollback
            if (!Schema::hasColumn('movies', 'genre')) {
                $table->string('genre', 100)->nullable();
            }

            // Xóa các cột thêm mới
            if (Schema::hasColumn('movies', 'language')) {
                $table->dropColumn('language');
            }

            if (Schema::hasColumn('movies', 'end_date')) {
                $table->dropColumn('end_date');
            }
        });
    }
};
