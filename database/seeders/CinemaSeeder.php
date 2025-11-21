<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cinema;

class CinemaSeeder extends Seeder
{
    public function run(): void
    {
        Cinema::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Smart Ticket',
                'address' => 'Số 18 Nguyễn Trãi, Phường Thượng Đình, Quận Thanh Xuân, Hà Nội',
                'phone' => '02466888899',
                'status' => 'active',
                'image' => 'cinema/rapchieuphim.jpg', // ảnh mặc định
                'description' => 'Rạp chiếu phim Smart Ticket là rạp hiện đại với 8 phòng chiếu, âm thanh chuẩn Dolby Atmos và màn hình lớn chất lượng cao.',
            ]
        );
    }
}
