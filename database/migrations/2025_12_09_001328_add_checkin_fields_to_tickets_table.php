<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // cờ check-in (false: chưa, true: đã check-in)
            $table->boolean('is_checked_in')
                ->default(false)
                ->after('qr_code');

            // thời điểm check-in
            $table->timestamp('checked_in_at')
                ->nullable()
                ->after('is_checked_in');

            // id nhân viên / user check-in (tuỳ bạn dùng bảng nào)
            $table->unsignedBigInteger('checked_in_by')
                ->nullable()
                ->after('checked_in_at');

            // nếu muốn đảm bảo mỗi qr_code là duy nhất:
            // chú ý: chỉ bật cái này khi bạn chắc chắn không có qr_code bị trùng
            $table->unique('qr_code', 'tickets_qr_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // nếu bạn có dùng foreign key cho checked_in_by thì dropForeign trước
            // $table->dropForeign(['checked_in_by']);

            $table->dropUnique('tickets_qr_code_unique');
            $table->dropColumn(['is_checked_in', 'checked_in_at', 'checked_in_by']);
        });
    }
};
