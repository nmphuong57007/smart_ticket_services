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
     * Nhận range hoặc from_date / to_date
     */
    public function getDashboardData(string $range, ?string $fromDate = null, ?string $toDate = null): array
    {
        // Ưu tiên lọc theo khoảng thời gian tùy chọn
        [$from, $to] = $this->resolveDateRange($range, $fromDate, $toDate);

        return [
            'summary'            => $this->summary($from, $to),
            'chart'              => $this->revenueChart($from, $to),
            'latest_bookings'    => $this->latestBookings(),
            'upcoming_showtimes' => $this->upcomingShowtimes(),
            'movies_statistics'  => $this->moviesStatistics($from, $to), // ⭐ MỚI
            'meta' => [
                'range' => $range,
                'from'  => $from->toDateString(),
                'to'    => $to->toDateString(),
            ],
        ];
    }

    /**
     * Xác định khoảng thời gian:
     * - Nếu có from_date & to_date → dùng theo user chọn
     * - Nếu không → fallback về range (today | 7d | 30d)
     */
    protected function resolveDateRange(string $range, ?string $fromDate, ?string $toDate): array
    {
        // Nếu user chọn khoảng thời gian bất kỳ
        if ($fromDate && $toDate) {
            return [
                Carbon::parse($fromDate)->startOfDay(),
                Carbon::parse($toDate)->endOfDay(),
            ];
        }

        // Nếu không có thì dùng preset
        $to = Carbon::now()->endOfDay();

        $from = match ($range) {
            '7d'  => Carbon::now()->subDays(6)->startOfDay(),
            '30d' => Carbon::now()->subDays(29)->startOfDay(),
            default => Carbon::now()->startOfDay(),
        };

        return [$from, $to];
    }

    /**
     * ==========================
     * 1. SUMMARY – 4 Ô THỐNG KÊ
     * ==========================
     */
    protected function summary(Carbon $from, Carbon $to): array
    {
        return [
            // Tổng doanh thu (chỉ tính booking đã thanh toán)
            'total_revenue' => Booking::where('payment_status', Booking::PAYMENT_PAID)
                ->whereBetween('created_at', [$from, $to])
                ->sum('final_amount'),

            // Tổng vé đã bán (đếm ghế booked)
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
     * ==========================
     * 2. BIỂU ĐỒ DOANH THU
     * ==========================
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
     * ==========================
     * 3. 5 ĐƠN VÉ MỚI NHẤT
     * ==========================
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
     * ==========================
     * 4. SUẤT CHIẾU SẮP DIỄN RA
     * ==========================
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
                $sold     = $showtime->seats->where('status', 'booked')->count();

                return [
                    'movie'    => $showtime->movie->title,
                    'date'     => $showtime->show_date,
                    'time'     => $showtime->show_time,
                    'room'     => $showtime->room->name,
                    'sold'     => $sold,
                    'capacity' => $capacity,
                ];
            });
    }

    /**
     * ==========================
     * 5. THỐNG KÊ THEO TỪNG PHIM
     * ==========================
     * Dùng để biết:
     * - Phim nào bán chạy
     * - Phim nào ít người xem
     */
    protected function moviesStatistics(Carbon $from, Carbon $to)
    {
        // Lấy tất cả suất chiếu trong khoảng thời gian
        $showtimes = Showtime::with(['movie', 'seats'])
            ->whereBetween('show_date', [
                $from->toDateString(),
                $to->toDateString()
            ])
            ->get()
            ->groupBy('movie_id');

        $result = [];

        foreach ($showtimes as $movieId => $items) {

            $movie = $items->first()->movie;

            // Tổng số ghế của tất cả suất chiếu phim đó
            $totalSeats = $items->sum(fn($st) => $st->seats->count());

            // Tổng số ghế đã bán
            $soldSeats = $items->sum(
                fn($st) =>
                $st->seats->where('status', 'booked')->count()
            );

            // Doanh thu của phim
            $revenue = Booking::whereHas('showtime', function ($q) use ($movieId) {
                $q->where('movie_id', $movieId);
            })
                ->where('payment_status', Booking::PAYMENT_PAID)
                ->whereBetween('created_at', [$from, $to])
                ->sum('final_amount');

            $result[] = [
                'movie_id'        => $movieId,
                'movie'           => $movie->title,
                'total_showtimes' => $items->count(),
                'total_seats'     => $totalSeats,
                'sold_tickets'    => $soldSeats,
                'empty_seats'     => max($totalSeats - $soldSeats, 0),
                'revenue'         => $revenue,
                'fill_percent'    => $totalSeats > 0
                    ? round(($soldSeats / $totalSeats) * 100)
                    : 0,
            ];
        }

        return $result;
    }
}
