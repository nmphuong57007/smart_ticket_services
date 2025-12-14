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
     * - range (optional):
     *   + today : thống kê hôm nay (mặc định)
     *   + 7d    : thống kê 7 ngày gần nhất
     *   + 30d   : thống kê 30 ngày gần nhất
     *
     * Frontend chỉ cần đổi range là có thể cập nhật toàn bộ dashboard.
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
                'range' => 'nullable|in:today,7d,30d',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tham số range không hợp lệ. Chỉ chấp nhận: today, 7d, 30d.',
                'errors'  => $e->errors(),
            ], 422);
        }

        /**
         * ==========================
         * 2. XÁC ĐỊNH KHOẢNG THỜI GIAN
         * ==========================
         * Nếu FE không truyền range thì mặc định là "today"
         */
        $range = $validated['range'] ?? 'today';

        /**
         * ==========================
         * 3. LẤY DỮ LIỆU DASHBOARD
         * ==========================
         * Toàn bộ logic xử lý nằm trong DashboardService
         */
        $dashboardData = $this->dashboardService->getDashboardData($range);

        /**
         * ==========================
         * 4. TRẢ RESPONSE CHO FRONTEND
         * ==========================
         * Dữ liệu được format thông qua DashboardResource
         */
        return response()->json([
            'success' => true,
            'message' => 'Lấy dữ liệu dashboard thành công.',
            'data'    => new DashboardResource($dashboardData),
        ]);
    }
}
