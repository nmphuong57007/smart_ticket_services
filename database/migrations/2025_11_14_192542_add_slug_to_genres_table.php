<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Genre;

return new class extends Migration {
    public function up(): void
    {
        // 1) Thêm cột slug nếu chưa có
        Schema::table('genres', function (Blueprint $table) {
            if (!Schema::hasColumn('genres', 'slug')) {
                $table->string('slug', 150)->nullable()->after('name');
            }
        });

        // 2) Generate slug cho dữ liệu cũ
        $genres = Genre::all();
        foreach ($genres as $genre) {
            if (!$genre->slug) {
                $genre->slug = Str::slug($genre->name);
                $genre->save();
            }
        }

        // 3) Thêm unique index (KHÔNG kiểm tra — vì slug đã unique)
        Schema::table('genres', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('genres', function (Blueprint $table) {
            if (Schema::hasColumn('genres', 'slug')) {
                $table->dropUnique(['slug']);
                $table->dropColumn('slug');
            }
        });
    }
};
