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
             * Dùng để hiển thị:
             * - Doanh thu
             * - Vé đã bán
             * - Suất chiếu
             * - Phim đang chiếu
             */
            'summary' => [
                // Tổng doanh thu (chỉ tính booking đã thanh toán)
                'total_revenue' => (float) $this['summary']['total_revenue'],

                // Tổng số vé đã bán (số ghế đã booked)
                'total_tickets' => (int) $this['summary']['total_tickets'],

                // Tổng số suất chiếu trong khoảng thời gian được chọn
                'total_showtimes' => (int) $this['summary']['total_showtimes'],

                // Tổng số phim đang chiếu
                'total_movies_showing' => (int) $this['summary']['total_movies_showing'],
            ],

            /**
             * ==========================
             * 2. CHART – BIỂU ĐỒ DOANH THU
             * ==========================
             * FE dùng để vẽ line chart / bar chart
             * - Trục X: date
             * - Trục Y: revenue
             */
            'chart' => $this['chart']->map(function ($item) {
                return [
                    // Ngày (YYYY-MM-DD)
                    'date' => $item->date,

                    // Doanh thu của ngày đó
                    'revenue' => (float) $item->revenue,
                ];
            }),

            /**
             * ==========================
             * 3. LATEST BOOKINGS – ĐƠN VÉ MỚI NHẤT
             * ==========================
             * Dùng để hiển thị bảng "Đơn vé mới nhất"
             */
            'latest_bookings' => $this['latest_bookings']->map(function ($booking) {
                return [
                    // ID booking (dùng khi cần click xem chi tiết)
                    'id' => $booking->id,

                    // Mã đơn vé
                    'booking_code' => $booking->booking_code,

                    // Tên khách hàng
                    'customer_name' => optional($booking->user)->fullname,

                    // Tên phim
                    'movie' => optional($booking->showtime->movie)->title,

                    // Tên phòng chiếu
                    'room' => optional($booking->showtime->room)->name,

                    // Tổng tiền đơn vé
                    'total_amount' => (float) $booking->final_amount,

                    // Trạng thái thanh toán: paid | pending | failed | refunded
                    'payment_status' => $booking->payment_status,

                    // Trạng thái booking: pending | paid | canceled | expired
                    'booking_status' => $booking->booking_status,

                    // Thời gian tạo đơn (format sẵn cho FE)
                    'created_at' => optional($booking->created_at)->format('Y-m-d H:i'),
                ];
            }),

            /**
             * ==========================
             * 4. UPCOMING SHOWTIMES – SUẤT CHIẾU SẮP DIỄN RA
             * ==========================
             * Dùng để hiển thị bảng suất chiếu bên dưới dashboard
             */
            'upcoming_showtimes' => collect($this['upcoming_showtimes'])->map(function ($showtime) {

                // Tính % lấp đầy (đã làm sẵn để FE không phải tính)
                $percent = $showtime['capacity'] > 0
                    ? round(($showtime['sold'] / $showtime['capacity']) * 100)
                    : 0;

                return [
                    // Tên phim
                    'movie' => $showtime['movie'],

                    // Ngày chiếu
                    'date' => $showtime['date'],

                    // Giờ chiếu
                    'time' => $showtime['time'],

                    // Phòng chiếu
                    'room' => $showtime['room'],

                    // Số vé đã bán
                    'sold' => (int) $showtime['sold'],

                    // Tổng số ghế
                    'capacity' => (int) $showtime['capacity'],

                    // Phần trăm lấp đầy (dùng cho progress bar)
                    'percent' => $percent,
                ];
            }),

            /**
             * ==========================
             * 5. META – THÔNG TIN BỘ LỌC
             * ==========================
             * FE dùng để biết dashboard đang ở:
             * - Hôm nay
             * - 7 ngày
             * - 30 ngày
             */
            'meta' => $this['meta'],
        ];
    }
}
