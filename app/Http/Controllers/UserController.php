<?php

namespace App\Http\Controllers;

use App\Http\Services\User\UserService;
use App\Http\Validator\User\GetUsersValidator;
use App\Http\Validator\User\UpdateUserValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    protected UserService $userService;
    protected GetUsersValidator $getUsersValidator;
    protected UpdateUserValidator $updateUserValidator;

    public function __construct(
        UserService $userService,
        GetUsersValidator $getUsersValidator,
        UpdateUserValidator $updateUserValidator
    ) {
        $this->userService = $userService;
        $this->getUsersValidator = $getUsersValidator;
        $this->updateUserValidator = $updateUserValidator;
    }

    /**
     * Get all users with pagination and filtering
     */
    public function index(Request $request)
    {
        try {
            // Check if user has admin or staff role
            if (!$this->userService->hasPermission($request->user(), 'view_users')) {
                return response([
                    'success' => false,
                    'message' => 'Không có quyền truy cập. Chỉ quản trị viên và nhân viên mới có thể xem danh sách người dùng.'
                ], 403);
            }

            $validationResult = $this->getUsersValidator->validateWithStatus($request->all());
            if (!$validationResult['success']) {
                return response([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validationResult['errors']
                ], 422);
            }

            $filters = $request->only(['search', 'role', 'status', 'sort_by', 'sort_order', 'per_page']);
            $users = $this->userService->getUsers($filters);

            return response([
                'success' => true,
                'message' => 'Lấy danh sách người dùng thành công',
                'data' => [
                    'users' => $users->items(),
                    'pagination' => [
                        'current_page' => $users->currentPage(),
                        'last_page' => $users->lastPage(),
                        'per_page' => $users->perPage(),
                        'total' => $users->total(),
                        'from' => $users->firstItem(),
                        'to' => $users->lastItem()
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Lấy danh sách người dùng thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user statistics
     */
    public function statistics(Request $request)
    {
        try {
            // Check if user has admin or staff role
            if (!$this->userService->hasPermission($request->user(), 'view_statistics')) {
                return response([
                    'success' => false,
                    'message' => 'Không có quyền truy cập. Chỉ quản trị viên và nhân viên mới có thể xem thống kê.'
                ], 403);
            }

            $stats = $this->userService->getUserStatistics();

            return response([
                'success' => true,
                'message' => 'Lấy thống kê người dùng thành công',
                'data' => $stats
            ], 200);
        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Lấy thống kê người dùng thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific user details (admin/staff only)
     */
    public function show(Request $request, $id)
    {
        try {
            // Check if user has admin or staff role
            if (!$this->userService->hasPermission($request->user(), 'view_user_details')) {
                return response([
                    'success' => false,
                    'message' => 'Không có quyền truy cập. Chỉ quản trị viên và nhân viên mới có thể xem thông tin người dùng.'
                ], 403);
            }

            $user = $this->userService->findUserById($id);

            return response([
                'success' => true,
                'message' => 'Lấy thông tin người dùng thành công',
                'data' => [
                    'user' => $user
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
                'message' => 'Lấy thông tin người dùng thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user information (admin only)
     */
    public function update(Request $request, $id)
    {
        try {
            // Check if user has admin role
            if (!$this->userService->hasPermission($request->user(), 'update_user')) {
                return response([
                    'success' => false,
                    'message' => 'Không có quyền truy cập. Chỉ quản trị viên mới có thể cập nhật thông tin người dùng.'
                ], 403);
            }

            $user = $this->userService->findUserById($id);

            $validationResult = $this->updateUserValidator->setUserId($id)->validateWithStatus($request->all());
            if (!$validationResult['success']) {
                return response([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validationResult['errors']
                ], 422);
            }

            // Update user (chỉ cho phép cập nhật thông tin cơ bản)
            $updatedUser = $this->userService->updateUser($user, $request->only(['fullname', 'email', 'phone', 'address', 'gender']));

            return response([
                'success' => true,
                'message' => 'Cập nhật người dùng thành công',
                'data' => [
                    'user' => $updatedUser
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
                'message' => 'Cập nhật người dùng thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update current user's profile
     */ public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'fullname' => 'sometimes|required|string|max:100',
                'email' => 'sometimes|required|email|max:100|unique:users,email,' . $user->id,
                'phone' => 'sometimes|nullable|string|max:20|unique:users,phone,' . $user->id,
                'address' => 'sometimes|nullable|string|max:255',
                'gender' => 'sometimes|nullable|in:male,female,other',
                'avatar' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,gif|max:2048', // 👈 thêm dòng này
            ], [
                'avatar.image' => 'Tệp tải lên phải là hình ảnh',
                'avatar.mimes' => 'Ảnh đại diện chỉ chấp nhận định dạng: jpg, jpeg, png, gif',
                'avatar.max' => 'Kích thước ảnh đại diện tối đa là 2MB',
            ]);

            if ($validator->fails()) {
                return response([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            // ✅ Xử lý upload avatar (nếu có)
            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('avatars', $filename, 'public'); // Lưu vào storage/app/public/avatars

                // Nếu người dùng đã có avatar cũ thì xóa đi (tuỳ chọn)
                if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                }

                // Cập nhật đường dẫn mới
                $user->avatar = $path;
            }

            // ✅ Update các thông tin khác
            $user->fill($request->only(['fullname', 'email', 'phone', 'address', 'gender']));
            $user->save();

            return response([
                'success' => true,
                'message' => 'Cập nhật thông tin cá nhân thành công',
                'data' => [
                    'user' => $user->fresh()
                ]
            ], 200);
        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Cập nhật thông tin cá nhân thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Block/Unblock user (admin only)
     */
    public function toggleStatus(Request $request, $id)
    {
        try {
            $targetUser = $this->userService->findUserById($id);

            // Check permission
            if (!$this->userService->hasPermission($request->user(), 'toggle_user_status', $targetUser)) {
                return response([
                    'success' => false,
                    'message' => 'Không có quyền truy cập. Chỉ quản trị viên mới có thể khóa/mở khóa tài khoản.'
                ], 403);
            }

            // Cannot block yourself
            if ($targetUser->id === $request->user()->id) {
                return response([
                    'success' => false,
                    'message' => 'Bạn không thể khóa tài khoản của chính mình'
                ], 400);
            }

            $updatedUser = $this->userService->toggleUserStatus($targetUser);
            $newStatus = $updatedUser->status;

            return response([
                'success' => true,
                'message' => $newStatus === 'active' ? 'Mở khóa tài khoản thành công' : 'Khóa tài khoản thành công',
                'data' => [
                    'user' => $updatedUser
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
                'message' => 'Thay đổi trạng thái người dùng thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete user (admin only)
     */
    public function destroy(Request $request, $id)
    {
        try {
            $targetUser = $this->userService->findUserById($id);

            // Check permission
            if (!$this->userService->hasPermission($request->user(), 'delete_user', $targetUser)) {
                return response([
                    'success' => false,
                    'message' => 'Không có quyền truy cập. Chỉ quản trị viên mới có thể xóa người dùng.'
                ], 403);
            }

            // Cannot delete yourself
            if ($targetUser->id === $request->user()->id) {
                return response([
                    'success' => false,
                    'message' => 'Bạn không thể xóa tài khoản của chính mình'
                ], 400);
            }

            $this->userService->deleteUser($targetUser);

            return response([
                'success' => true,
                'message' => 'Xóa người dùng thành công'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response([
                'success' => false,
                'message' => 'Không tìm thấy người dùng'
            ], 404);
        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Xóa người dùng thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function changePassword(Request $request)
    {
        // 1. Validation
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|confirmed|min:6',
        ]);

        // Lấy người dùng hiện tại đang đăng nhập
        $user = $request->user();

        // 2. Xác thực Mật khẩu Cũ (Quan trọng nhất)
        // Kiểm tra xem mật khẩu hiện tại có khớp với mật khẩu người dùng đang dùng không
        if (!Hash::check($request->current_password, $user->password)) {
            // Throw ValidationException để trả về lỗi 422 JSON
            throw ValidationException::withMessages([
                'current_password' => ['Mật khẩu hiện tại không chính xác.'],
            ]);
        }

        // 3. Cập nhật Mật khẩu Mới
        $user->password = Hash::make($request->password);
        $user->save();

        // Tùy chọn: Hủy bỏ tất cả các token khác (để yêu cầu đăng nhập lại)
        // if ($user->tokens()) {
        //     $user->tokens()->where('id', '!=', $request->bearerToken())->delete();
        // }


        // 4. Trả về phản hồi thành công
        return response()->json([
            'message' => 'Mật khẩu đã được thay đổi thành công.',
        ], 200);
    }
}
