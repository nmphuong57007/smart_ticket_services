<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\PointsHistory;

class PointsHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lấy user admin để làm người thực hiện
        $admin = User::where('role', 'admin')->first();
        
        // Lấy một số user để tạo lịch sử điểm (theo cấu hình)
        $usersToUse = (int) config('seeder.points_history_users', 50) * (int) config('seeder.multiplier', 1);
        $users = User::where('role', 'customer')->take($usersToUse)->get();
        
        if ($users->isEmpty()) {
            $this->command->info('Không có customer nào để tạo lịch sử điểm. Vui lòng tạo user trước.');
            return;
        }

        $insertData = [];
        $now = now();
        $txPerUser = (int) config('seeder.points_transactions_per_user', 7) * (int) config('seeder.multiplier', 1);

        foreach ($users as $user) {
            $currentPoints = 0;

            for ($t = 0; $t < $txPerUser; $t++) {
                // random transaction
                $points = [100, 50, -30, 25, 200, 75, -50][array_rand([100,50,-30,25,200,75,-50])];
                $type = $points > 0 ? 'earned' : 'spent';
                $source = ['booking','review','promotion','referral','birthday','registration'][array_rand(['booking','review','promotion','referral','birthday','registration'])];

                $balanceBefore = $currentPoints;
                $currentPoints += $points;

                $createdAt = now()->subDays(rand(0, 365))->subHours(rand(0,23));

                $insertData[] = [
                    'user_id' => $user->id,
                    'points' => $points,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $currentPoints,
                    'type' => $type,
                    'source' => $source,
                    'reference_type' => null,
                    'reference_id' => null,
                    'description' => ucfirst($type) . ' via ' . $source,
                    'metadata' => null,
                    'created_by' => $admin?->id,
                    'created_at' => $createdAt->toDateTimeString(),
                    'updated_at' => $createdAt->toDateTimeString(),
                ];
            }

            // update last points value
            $user->update(['points' => $currentPoints]);
        }

        // bulk insert
        foreach (array_chunk($insertData, 2000) as $chunk) {
            \Illuminate\Support\Facades\DB::table('points_history')->insert($chunk);
        }
    }
}
