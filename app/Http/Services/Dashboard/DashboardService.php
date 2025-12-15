<?php

namespace App\Http\Services\Dashboard;

use App\Models\Booking;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\Movie;
use Carbon\Carbon;

class DashboardService
{
    /**
     * API chính cho Dashboard
     */
    public function getDashboardData(string $range): array
    {
        [$from, $to] = $this->resolveDateRange($range);

        return [
            'summary' => $this->summary($from, $to),
            'chart' => $this->revenueChart($from, $to),
            'latest_bookings' => $this->latestBookings(),
            'upcoming_showtimes' => $this->upcomingShowtimes(),
            'meta' => [
                'range' => $range,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ];
    }

    /**
     * Xác định khoảng thời gian theo range
     * today | 7d | 30d
     */
    protected function resolveDateRange(string $range): array
    {
        $to = Carbon::now()->endOfDay();

        $from = match ($range) {
            '7d'  => Carbon::now()->subDays(6)->startOfDay(),
            '30d' => Carbon::now()->subDays(29)->startOfDay(),
            default => Carbon::now()->startOfDay(),
        };

        return [$from, $to];
    }

    /**
     * 4 box thống kê phía trên Dashboard
     */
    protected function summary(Carbon $from, Carbon $to): array
    {
        return [
            // Tổng doanh thu (chỉ booking đã thanh toán)
            'total_revenue' => Booking::where('payment_status', Booking::PAYMENT_PAID)
                ->whereBetween('created_at', [$from, $to])
                ->sum('final_amount'),

            // Tổng vé đã bán = số ghế đã booked
            'total_tickets' => Seat::where('status', 'booked')
                ->whereBetween('updated_at', [$from, $to])
                ->count(),

            // Tổng số suất chiếu trong khoảng thời gian
            'total_showtimes' => Showtime::whereBetween('show_date', [
                $from->toDateString(),
                $to->toDateString()
            ])->count(),

            // Tổng số phim đang chiếu
            'total_movies_showing' => Movie::where('status', 'showing')->count(),
        ];
    }

    /**
     * Biểu đồ doanh thu theo ngày
     */
    protected function revenueChart(Carbon $from, Carbon $to)
    {
        return Booking::where('payment_status', Booking::PAYMENT_PAID)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as date, SUM(final_amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * 5 đơn vé mới nhất
     */
    protected function latestBookings()
    {
        return Booking::with([
            'user',
            'showtime.movie',
            'showtime.room'
        ])
            ->orderByDesc('id')
            ->limit(5)
            ->get();
    }

    /**
     * 5 suất chiếu sắp diễn ra
     * (đã tối ưu tránh N+1 query)
     */
    protected function upcomingShowtimes()
    {
        return Showtime::with([
            'movie',
            'room',
            'seats'
        ])
            ->where(function ($q) {
                $q->where('show_date', '>', now()->toDateString())
                    ->orWhere(function ($q) {
                        $q->where('show_date', now()->toDateString())
                            ->where('show_time', '>=', now()->format('H:i:s'));
                    });
            })
            ->orderBy('show_date')
            ->orderBy('show_time')
            ->limit(5)
            ->get()
            ->map(function ($showtime) {

                $capacity = $showtime->seats->count();
                $sold = $showtime->seats->where('status', 'booked')->count();

                return [
                    'movie' => $showtime->movie->title,
                    'date' => $showtime->show_date,
                    'time' => $showtime->show_time,
                    'room' => $showtime->room->name,
                    'sold' => $sold,
                    'capacity' => $capacity,
                ];
            });
    }
}
