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
        
        // Lấy một số user để tạo lịch sử điểm
        $users = User::where('role', 'customer')->take(3)->get();
        
        if ($users->isEmpty()) {
            $this->command->info('Không có customer nào để tạo lịch sử điểm. Vui lòng tạo user trước.');
            return;
        }

        foreach ($users as $user) {
            // Đặt điểm ban đầu cho user
            $currentPoints = 0;
            
            // Tạo các giao dịch mẫu
            $transactions = [
                [
                    'points' => 100,
                    'type' => 'bonus',
                    'source' => 'registration',
                    'description' => 'Thưởng đăng ký tài khoản mới',
                    'metadata' => ['promotion_code' => 'WELCOME100']
                ],
                [
                    'points' => 50,
                    'type' => 'earned',
                    'source' => 'booking',
                    'description' => 'Tích điểm từ đặt vé xem phim',
                    'reference_type' => 'booking',
                    'reference_id' => rand(1, 10),
                    'metadata' => ['movie' => 'Avengers: Endgame', 'tickets' => 2]
                ],
                [
                    'points' => -30,
                    'type' => 'spent',
                    'source' => 'booking',
                    'description' => 'Sử dụng điểm giảm giá vé xem phim',
                    'reference_type' => 'booking',
                    'reference_id' => rand(11, 20),
                    'metadata' => ['discount_amount' => 30000]
                ],
                [
                    'points' => 25,
                    'type' => 'earned',
                    'source' => 'review',
                    'description' => 'Thưởng viết đánh giá phim',
                    'reference_type' => 'review',
                    'reference_id' => rand(1, 5),
                    'metadata' => ['movie' => 'Spider-Man: No Way Home', 'rating' => 5]
                ],
                [
                    'points' => 200,
                    'type' => 'bonus',
                    'source' => 'birthday',
                    'description' => 'Thưởng sinh nhật',
                    'metadata' => ['birthday_year' => 2024]
                ],
                [
                    'points' => 75,
                    'type' => 'earned',
                    'source' => 'referral',
                    'description' => 'Thưởng giới thiệu bạn bè',
                    'reference_type' => 'user',
                    'reference_id' => rand(1, 100),
                    'metadata' => ['referred_user_email' => 'friend@example.com']
                ],
                [
                    'points' => -50,
                    'type' => 'spent',
                    'source' => 'promotion',
                    'description' => 'Đổi điểm lấy combo bắp nước',
                    'reference_type' => 'promotion',
                    'reference_id' => rand(1, 3),
                    'metadata' => ['combo_name' => 'Combo Medium']
                ]
            ];

            foreach ($transactions as $transaction) {
                $balanceBefore = $currentPoints;
                $currentPoints += $transaction['points'];
                $balanceAfter = $currentPoints;

                PointsHistory::create([
                    'user_id' => $user->id,
                    'points' => $transaction['points'],
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'type' => $transaction['type'],
                    'source' => $transaction['source'],
                    'reference_type' => $transaction['reference_type'] ?? null,
                    'reference_id' => $transaction['reference_id'] ?? null,
                    'description' => $transaction['description'],
                    'metadata' => json_encode($transaction['metadata'] ?? null),
                    'created_by' => ($transaction['source'] === 'manual') ? $admin?->id : null,
                    'created_at' => now()->subDays(rand(1, 30))->subHours(rand(0, 23))
                ]);
            }

            // Cập nhật điểm cuối cùng cho user
            $user->update(['points' => $currentPoints]);
        }
    }
}
