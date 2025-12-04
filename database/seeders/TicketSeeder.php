<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ticket;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        Ticket::create([
            'booking_id' => 1,
            'seat_id' => 1,
            'qr_code' => 'TEST-QR-1'
        ]);

        Ticket::create([
            'booking_id' => 1,
            'seat_id' => 2,
            'qr_code' => 'TEST-QR-2'
        ]);
    }
}
