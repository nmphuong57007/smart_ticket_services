<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        // Tạo nhiều customer theo cấu hình
        $count = (int) config('seeder.customers', 1000) * (int) config('seeder.multiplier', 1);

        // Giữ lại admin/staff/customer mẫu nếu đã tồn tại (AdminUserSeeder)
        // Factory tạo nhiều trường có thể không khớp schema (vd. email_verified_at),
        // nên tạo mảng dữ liệu và bulk-insert vào bảng `users` để tránh lỗi column not found.
        $models = User::factory()->count($count)->make();

        $now = now()->toDateTimeString();
        $rows = [];
        // Chỉ giữ các cột hiện có trong schema users để tránh lỗi khi bulk-insert
        $allowed = ['fullname','email','phone','avatar','password','role','points','status','created_at','updated_at'];
        // Ensure generated phones are unique (schema enforces unique on `phone`).
        // Use a stable numeric base to avoid collisions with AdminUserSeeder phones.
        $start = intval(microtime(true)) % 10000000;
        $i = 0;
        foreach ($models as $m) {
            $i++;
            $attrs = $m->getAttributes();
            $row = [];
            foreach ($allowed as $col) {
                // Force deterministic unique values for email and phone to avoid
                // unique constraint collisions when bulk-inserting large batches.
                if ($col === 'phone') {
                    $row['phone'] = sprintf('+849%08d', $start + $i);
                    continue;
                }
                if ($col === 'email') {
                    $row['email'] = sprintf('seed_user_%d+%d@example.local', $start, $i);
                    continue;
                }

                if (array_key_exists($col, $attrs)) {
                    $row[$col] = $attrs[$col];
                }
            }
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
            $rows[] = $row;
        }

        foreach (array_chunk($rows, 1000) as $chunk) {
            \Illuminate\Support\Facades\DB::table('users')->insert($chunk);
        }

        $this->command->info("✅ Đã tạo {$count} user (customers) thành công (bulk insert).");
    }
}
