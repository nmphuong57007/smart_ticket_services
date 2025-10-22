<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Tạo admin user mặc định
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@smartticket.com'],
            [
                'fullname' => 'System Administrator',
                'email' => 'admin@smartticket.com',
                'phone' => '0901234567',
                'avatar' => 'https://picsum.photos/id/2000/200/200',
                'password' => Hash::make('admin123456'),
                'role' => 'admin',
                'points' => 0,
                'status' => 'active'
            ]
        );

        // Tạo staff user mẫu
        $staffUser = User::firstOrCreate(
            ['email' => 'staff@smartticket.com'],
            [
                'fullname' => 'Staff Manager',
                'email' => 'staff@smartticket.com',
                'phone' => '0912345678',
                'avatar' => 'https://picsum.photos/id/2001/200/200',
                'password' => Hash::make('staff123456'),
                'role' => 'staff',
                'points' => 0,
                'status' => 'active'
            ]
        );

        // Tạo customer user mẫu
        $customerUser = User::firstOrCreate(
            ['email' => 'customer@example.com'],
            [
                'fullname' => 'John Customer',
                'email' => 'customer@example.com',
                'phone' => '0923456789',
                'avatar' => 'https://picsum.photos/id/2002/200/200',
                'password' => Hash::make('customer123'),
                'role' => 'customer',
                'points' => 100,
                'status' => 'active'
            ]
        );
    }
}
