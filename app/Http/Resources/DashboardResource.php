<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * DashboardResource
 *
 * Resource này dùng để format dữ liệu cho trang Dashboard Admin.
 * Frontend chỉ cần render UI, KHÔNG phải xử lý logic hay tính toán lại.
 */
class DashboardResource extends JsonResource
{
    /**
     * Chuyển dữ liệu dashboard sang JSON cho frontend
     */
    public function toArray($request)
    {
        return [

            /**
             * ==========================
             * 1. SUMMARY – CÁC Ô THỐNG KÊ TRÊN CÙNG
             * ==========================
             */
            'summary' => [
                'total_revenue'        => (float) $this['summary']['total_revenue'],
                'total_tickets'        => (int) $this['summary']['total_tickets'],
                'total_showtimes'      => (int) $this['summary']['total_showtimes'],
                'total_movies_showing' => (int) $this['summary']['total_movies_showing'],
            ],

            /**
             * ==========================
             * 2. CHART – BIỂU ĐỒ DOANH THU
             * ==========================
             */
            'chart' => $this['chart']->map(function ($item) {
                return [
                    'date'    => $item->date,
                    'revenue' => (float) $item->revenue,
                ];
            }),

            /**
             * ==========================
             * 3. LATEST BOOKINGS – ĐƠN VÉ MỚI NHẤT
             * ==========================
             */
            'latest_bookings' => $this['latest_bookings']->map(function ($booking) {
                return [
                    'id'             => $booking->id,
                    'booking_code'   => $booking->booking_code,
                    'customer_name'  => optional($booking->user)->fullname,
                    'movie'          => optional($booking->showtime->movie)->title,
                    'room'           => optional($booking->showtime->room)->name,
                    'total_amount'   => (float) $booking->final_amount,
                    'payment_status' => $booking->payment_status,
                    'booking_status' => $booking->booking_status,
                    'created_at'     => optional($booking->created_at)->format('Y-m-d H:i'),
                ];
            }),

            /**
             * ==========================
             * 4. UPCOMING SHOWTIMES – SUẤT CHIẾU SẮP DIỄN RA
             * ==========================
             */
            'upcoming_showtimes' => collect($this['upcoming_showtimes'])->map(function ($showtime) {

                $percent = $showtime['capacity'] > 0
                    ? round(($showtime['sold'] / $showtime['capacity']) * 100)
                    : 0;

                return [
                    'movie'    => $showtime['movie'],
                    'date'     => $showtime['date'],
                    'time'     => $showtime['time'],
                    'room'     => $showtime['room'],
                    'sold'     => (int) $showtime['sold'],
                    'capacity' => (int) $showtime['capacity'],
                    'percent'  => $percent,
                ];
            }),

            /**
             * ==========================
             * 5. MOVIES STATISTICS – THỐNG KÊ THEO TỪNG PHIM 
             * ==========================
             * Dùng để biết phim nào:
             * - Bán chạy
             * - Ít người xem
             * - Hiệu quả / không hiệu quả
             */
            'movies_statistics' => collect($this['movies_statistics'])->map(function ($movie) {
                return [
                    'movie_id'        => $movie['movie_id'],
                    'movie'           => $movie['movie'],
                    'total_showtimes' => (int) $movie['total_showtimes'],
                    'total_seats'     => (int) $movie['total_seats'],
                    'sold_tickets'    => (int) $movie['sold_tickets'],
                    'empty_seats'     => (int) $movie['empty_seats'],
                    'revenue'         => (float) $movie['revenue'],
                    'fill_percent'    => (int) $movie['fill_percent'],
                ];
            }),

            /**
             * ==========================
             * 6. META – THÔNG TIN BỘ LỌC
             * ==========================
             */
            'meta' => $this['meta'],
        ];
    }
}
