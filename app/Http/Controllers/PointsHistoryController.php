<?php

namespace App\Http\Controllers;

use App\Models\PointsHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PointsHistoryController extends Controller
{
    /**
     * Lấy lịch sử điểm của người dùng hiện tại
     */
    public function myHistory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'type' => 'nullable|in:earned,spent,refunded,bonus,penalty',
                'source' => 'nullable|string|max:100',
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after_or_equal:from_date'
            ], [
                'page.integer' => 'Số trang phải là số nguyên',
                'page.min' => 'Số trang phải lớn hơn 0',
                'per_page.integer' => 'Số bản ghi mỗi trang phải là số nguyên',
                'per_page.min' => 'Số bản ghi mỗi trang phải lớn hơn 0',
                'per_page.max' => 'Số bản ghi mỗi trang không được vượt quá 100',
                'type.in' => 'Loại giao dịch phải là một trong: earned, spent, refunded, bonus, penalty',
                'source.string' => 'Nguồn phải là chuỗi ký tự',
                'source.max' => 'Nguồn không được vượt quá 100 ký tự',
                'from_date.date' => 'Từ ngày phải là định dạng ngày hợp lệ',
                'to_date.date' => 'Đến ngày phải là định dạng ngày hợp lệ',
                'to_date.after_or_equal' => 'Đến ngày phải sau hoặc bằng từ ngày'
            ]);

            if ($validator->fails()) {
                return response([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();
            $query = $user->pointsHistory()->withCreator();

            // Lọc theo loại giao dịch
            if ($request->type) {
                $query->byType($request->type);
            }

            // Lọc theo nguồn
            if ($request->source) {
                $query->bySource($request->source);
            }

            // Lọc theo khoảng thời gian
            if ($request->from_date) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            if ($request->to_date) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            // Sắp xếp mới nhất trước
            $query->latest();

            // Phân trang
            $perPage = $request->per_page ?? 15;
            $history = $query->paginate($perPage);

            // Thống kê tổng quan
            $statistics = [
                'current_balance' => $user->points,
                'total_earned' => $user->pointsHistory()->where('points', '>', 0)->sum('points'),
                'total_spent' => abs($user->pointsHistory()->where('points', '<', 0)->sum('points')),
                'total_transactions' => $user->pointsHistory()->count()
            ];

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
            if (!in_array($request->user()->role, ['admin', 'staff'])) {
                return response([
                    'success' => false,
                    'message' => 'Không có quyền truy cập. Chỉ quản trị viên và nhân viên mới có thể xem lịch sử điểm của người dùng khác.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'type' => 'nullable|in:earned,spent,refunded,bonus,penalty',
                'source' => 'nullable|string|max:100',
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after_or_equal:from_date'
            ], [
                'page.integer' => 'Số trang phải là số nguyên',
                'page.min' => 'Số trang phải lớn hơn 0',
                'per_page.integer' => 'Số bản ghi mỗi trang phải là số nguyên',
                'per_page.min' => 'Số bản ghi mỗi trang phải lớn hơn 0',
                'per_page.max' => 'Số bản ghi mỗi trang không được vượt quá 100',
                'type.in' => 'Loại giao dịch phải là một trong: earned, spent, refunded, bonus, penalty',
                'source.string' => 'Nguồn phải là chuỗi ký tự',
                'source.max' => 'Nguồn không được vượt quá 100 ký tự',
                'from_date.date' => 'Từ ngày phải là định dạng ngày hợp lệ',
                'to_date.date' => 'Đến ngày phải là định dạng ngày hợp lệ',
                'to_date.after_or_equal' => 'Đến ngày phải sau hoặc bằng từ ngày'
            ]);

            if ($validator->fails()) {
                return response([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::find($userId);
            if (!$user) {
                return response([
                    'success' => false,
                    'message' => 'Không tìm thấy người dùng'
                ], 404);
            }

            $query = $user->pointsHistory()->withCreator();

            // Áp dụng các filter giống như myHistory
            if ($request->type) {
                $query->byType($request->type);
            }
            if ($request->source) {
                $query->bySource($request->source);
            }
            if ($request->from_date) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            if ($request->to_date) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            $query->latest();
            $perPage = $request->per_page ?? 15;
            $history = $query->paginate($perPage);

            $statistics = [
                'current_balance' => $user->points,
                'total_earned' => $user->pointsHistory()->where('points', '>', 0)->sum('points'),
                'total_spent' => abs($user->pointsHistory()->where('points', '<', 0)->sum('points')),
                'total_transactions' => $user->pointsHistory()->count()
            ];

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
            if ($request->user()->role !== 'admin') {
                return response([
                    'success' => false,
                    'message' => 'Không có quyền truy cập. Chỉ quản trị viên mới có thể cộng điểm thủ công.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'points' => 'required|integer|not_in:0',
                'type' => 'required|in:earned,spent,refunded,bonus,penalty',
                'description' => 'required|string|max:255',
                'metadata' => 'nullable|array'
            ], [
                'user_id.required' => 'ID người dùng không được để trống',
                'user_id.exists' => 'Không tìm thấy người dùng',
                'points.required' => 'Số điểm không được để trống',
                'points.integer' => 'Số điểm phải là số nguyên',
                'points.not_in' => 'Số điểm không được bằng 0',
                'type.required' => 'Loại giao dịch không được để trống',
                'type.in' => 'Loại giao dịch phải là một trong: earned, spent, refunded, bonus, penalty',
                'description.required' => 'Mô tả không được để trống',
                'description.string' => 'Mô tả phải là chuỗi ký tự',
                'description.max' => 'Mô tả không được vượt quá 255 ký tự',
                'metadata.array' => 'Metadata phải là mảng'
            ]);

            if ($validator->fails()) {
                return response([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::find($request->user_id);
            
            // Thêm điểm với lịch sử
            $pointsHistory = $user->addPoints(
                $request->points,
                $request->type,
                'manual',
                $request->description,
                [
                    'metadata' => $request->metadata,
                    'created_by' => $request->user()->id
                ]
            );

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
            $pointsHistory = PointsHistory::with(['user:id,fullname,email'])
                ->withCreator()
                ->find($id);

            if (!$pointsHistory) {
                return response([
                    'success' => false,
                    'message' => 'Không tìm thấy giao dịch điểm'
                ], 404);
            }

            // Kiểm tra quyền: chỉ được xem giao dịch của mình hoặc admin/staff xem tất cả
            $currentUser = $request->user();
            if ($currentUser->role === 'customer' && $pointsHistory->user_id !== $currentUser->id) {
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

        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Lấy chi tiết giao dịch điểm thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
