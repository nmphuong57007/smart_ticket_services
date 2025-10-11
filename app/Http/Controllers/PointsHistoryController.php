<?php

namespace App\Http\Controllers;

use App\Http\Services\PointsHistory\PointsHistoryService;
use App\Http\Validator\PointsHistory\PointsHistoryFilterValidator;
use App\Http\Validator\PointsHistory\AddPointsValidator;
use App\Models\PointsHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PointsHistoryController extends Controller
{
    protected PointsHistoryService $pointsHistoryService;
    protected PointsHistoryFilterValidator $filterValidator;
    protected AddPointsValidator $addPointsValidator;

    public function __construct(
        PointsHistoryService $pointsHistoryService,
        PointsHistoryFilterValidator $filterValidator,
        AddPointsValidator $addPointsValidator
    ) {
        $this->pointsHistoryService = $pointsHistoryService;
        $this->filterValidator = $filterValidator;
        $this->addPointsValidator = $addPointsValidator;
    }

    /**
     * Lấy lịch sử điểm của người dùng hiện tại
     */
    public function myHistory(Request $request)
    {
        try {
            $validationResult = $this->filterValidator->validateWithStatus($request->all());
            if (!$validationResult['success']) {
                return response([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validationResult['errors']
                ], 422);
            }

            $user = $request->user();

            $filters = $request->only(['type', 'source', 'from_date', 'to_date', 'per_page']);
            $history = $this->pointsHistoryService->getUserPointsHistory($user, $filters);

            // Thống kê tổng quan
            $statistics = $this->pointsHistoryService->getUserPointsStatistics($user);

            return response([
                'success' => true,
                'message' => 'Lấy lịch sử điểm thành công',
                'data' => [
                    'statistics' => $statistics,
                    'history' => $history->items(),
                    'pagination' => [
                        'current_page' => $history->currentPage(),
                        'last_page' => $history->lastPage(),
                        'per_page' => $history->perPage(),
                        'total' => $history->total(),
                        'from' => $history->firstItem(),
                        'to' => $history->lastItem()
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Lấy lịch sử điểm thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy lịch sử điểm của người dùng cụ thể (admin/staff only)
     */
    public function userHistory(Request $request, $userId)
    {
        try {
            // Kiểm tra quyền admin/staff
            if (!$this->pointsHistoryService->hasPermission($request->user(), 'view_user_history')) {
                return response([
                    'success' => false,
                    'message' => 'Không có quyền truy cập. Chỉ quản trị viên và nhân viên mới có thể xem lịch sử điểm của người dùng khác.'
                ], 403);
            }

            $validationResult = $this->filterValidator->validateWithStatus($request->all());
            if (!$validationResult['success']) {
                return response([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validationResult['errors']
                ], 422);
            }

            $user = User::findOrFail($userId);

            $filters = $request->only(['type', 'source', 'from_date', 'to_date', 'per_page']);
            $history = $this->pointsHistoryService->getUserPointsHistory($user, $filters);
            $statistics = $this->pointsHistoryService->getUserPointsStatistics($user);

            return response([
                'success' => true,
                'message' => 'Lấy lịch sử điểm người dùng thành công',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'fullname' => $user->fullname,
                        'email' => $user->email,
                        'points' => $user->points
                    ],
                    'statistics' => $statistics,
                    'history' => $history->items(),
                    'pagination' => [
                        'current_page' => $history->currentPage(),
                        'last_page' => $history->lastPage(),
                        'per_page' => $history->perPage(),
                        'total' => $history->total(),
                        'from' => $history->firstItem(),
                        'to' => $history->lastItem()
                    ]
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response([
                'success' => false,
                'message' => 'Không tìm thấy người dùng'
            ], 404);
        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Lấy lịch sử điểm người dùng thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Thêm điểm thủ công cho người dùng (admin only)
     */
    public function addPoints(Request $request)
    {
        try {
            // Chỉ admin mới được phép
            if (!$this->pointsHistoryService->hasPermission($request->user(), 'add_points_manually')) {
                return response([
                    'success' => false,
                    'message' => 'Không có quyền truy cập. Chỉ quản trị viên mới có thể cộng điểm thủ công.'
                ], 403);
            }

            $validationResult = $this->addPointsValidator->validateWithStatus($request->all());
            if (!$validationResult['success']) {
                return response([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validationResult['errors']
                ], 422);
            }

            $user = User::findOrFail($request->user_id);

            // Thêm điểm với lịch sử
            $data = $request->only(['points', 'type', 'description', 'metadata']);
            $pointsHistory = $this->pointsHistoryService->addPointsManually($user, $data, $request->user());

            return response([
                'success' => true,
                'message' => 'Cộng điểm thủ công thành công',
                'data' => [
                    'points_history' => $pointsHistory->load(['user:id,fullname,email', 'creator:id,fullname,email,role']),
                    'user_balance' => $user->fresh()->points,
                    'performed_by' => $pointsHistory->performed_by
                ]
            ], 201);

        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Cộng điểm thủ công thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy chi tiết giao dịch điểm
     */
    public function show(Request $request, $id)
    {
        try {
            $pointsHistory = $this->pointsHistoryService->findPointsHistoryById($id);

            // Kiểm tra quyền: chỉ được xem giao dịch của mình hoặc admin/staff xem tất cả
            $currentUser = $request->user();
            if (!$this->pointsHistoryService->canViewPointsHistory($currentUser, $pointsHistory)) {
                return response([
                    'success' => false,
                    'message' => 'Không có quyền truy cập. Bạn chỉ có thể xem giao dịch của chính mình.'
                ], 403);
            }

            return response([
                'success' => true,
                'message' => 'Lấy chi tiết giao dịch điểm thành công',
                'data' => [
                    'points_history' => $pointsHistory
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response([
                'success' => false,
                'message' => 'Không tìm thấy giao dịch điểm'
            ], 404);
        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Lấy chi tiết giao dịch điểm thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
