<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign('tickets_seat_id_foreign');
            $table->dropColumn('seat_id');

            $table->unique('booking_id');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('seat_id')->nullable()->after('booking_id');
            $table->dropUnique(['booking_id']);
            $table->dropUnique(['qr_code']);
        });
    }
};