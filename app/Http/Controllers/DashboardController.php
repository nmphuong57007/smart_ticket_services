<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\Dashboard\DashboardService;
use App\Http\Resources\DashboardResource;
use Illuminate\Validation\ValidationException;

/**
 * DashboardController
 *
 * Controller dùng để cung cấp dữ liệu cho trang Dashboard Admin.
 * API này gom toàn bộ thống kê vào 1 endpoint duy nhất.
 */
class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Lấy dữ liệu Dashboard
     *
     * API: GET /api/dashboard
     *
     * Query params:
     * - range (optional): today | 7d | 30d
     * - from_date (optional): YYYY-MM-DD
     * - to_date   (optional): YYYY-MM-DD
     */
    public function index(Request $request)
    {
        /**
         * ==========================
         * 1. VALIDATE QUERY PARAM
         * ==========================
         */
        try {
            $validated = $request->validate([
                // Preset có sẵn (như cũ)
                'range' => 'nullable|in:today,7d,30d',

                // Khoảng thời gian tùy chọn (theo góp ý của thầy)
                'from_date' => 'nullable|date',
                'to_date'   => 'nullable|date|after_or_equal:from_date',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tham số lọc dashboard không hợp lệ.',
                'errors'  => $e->errors(),
            ], 422);
        }

        /**
         * ==========================
         * 2. LẤY THAM SỐ FILTER
         * ==========================
         */
        // Nếu FE không truyền range thì mặc định là today
        $range = $validated['range'] ?? 'today';

        // Khoảng thời gian tùy chọn
        $fromDate = $validated['from_date'] ?? null;
        $toDate   = $validated['to_date'] ?? null;

        /**
         * ==========================
         * 3. LẤY DỮ LIỆU DASHBOARD
         * ==========================
         * Toàn bộ logic xử lý nằm trong DashboardService
         */
        $dashboardData = $this->dashboardService->getDashboardData(
            $range,
            $fromDate,
            $toDate
        );

        /**
         * ==========================
         * 4. TRẢ RESPONSE CHO FRONTEND
         * ==========================
         */
        return response()->json([
            'success' => true,
            'message' => 'Lấy dữ liệu dashboard thành công.',
            'data'    => new DashboardResource($dashboardData),
        ]);
    }
}
