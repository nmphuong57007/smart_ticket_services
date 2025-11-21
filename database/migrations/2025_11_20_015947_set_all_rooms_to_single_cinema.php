<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Gán tất cả phòng chiếu vào rạp duy nhất có id = 1
        DB::table('rooms')->update(['cinema_id' => 1]);
    }

    public function down(): void
    {
        // (không cần làm gì)
    }
};
