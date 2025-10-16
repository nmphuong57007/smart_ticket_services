<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;


class UserController extends Controller
{
    /**
     * Get all users with pagination and filtering
     */
    public function index(Request $request)
    {
        try {
            // Check if user has admin or staff role
            if (!in_array($request->user()->role, ['admin', 'staff'])) {
                return response([
                    'success' => false,
                    'message' => 'Không có quyền truy cập. Chỉ quản trị viên và nhân viên mới có thể xem danh sách người dùng.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'search' => 'nullable|string|max:255',
                'role' => 'nullable|in:customer,staff,admin',
                'status' => 'nullable|in:active,blocked',
                'sort_by' => 'nullable|in:id,fullname,email,created_at,points',
                'sort_order' => 'nullable|in:asc,desc'
            ], [
                'page.integer' => 'Số trang phải là số nguyên',
                'page.min' => 'Số trang phải lớn hơn 0',
                'per_page.integer' => 'Số bản ghi mỗi trang phải là số nguyên',
                'per_page.min' => 'Số bản ghi mỗi trang phải lớn hơn 0',
                'per_page.max' => 'Số bản ghi mỗi trang không được vượt quá 100',
                'search.string' => 'Từ khóa tìm kiếm phải là chuỗi ký tự',
                'search.max' => 'Từ khóa tìm kiếm không được vượt quá 255 ký tự',
                'role.in' => 'Vai trò phải là một trong: customer, staff, admin',
                'status.in' => 'Trạng thái phải là một trong: active, blocked',
                'sort_by.in' => 'Trường sắp xếp phải là một trong: id, fullname, email, created_at, points',
                'sort_order.in' => 'Hướng sắp xếp phải là asc hoặc desc'
            ]);

            if ($validator->fails()) {
                return response([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Query builder
            $query = User::query();

            // Search filter
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('fullname', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            // Role filter
            if ($request->role) {
                $query->where('role', $request->role);
            }

            // Status filter
            if ($request->status) {
                $query->where('status', $request->status);
            }

            // Sorting - Default: ID từ lớn đến bé (mới nhất trước)
            $sortBy = $request->sort_by ?? 'id';
            $sortOrder = $request->sort_order ?? 'desc';
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->per_page ?? 15;
            $users = $query->paginate($perPage);

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
            if (!in_array($request->user()->role, ['admin', 'staff'])) {
                return response([
                    'success' => false,
                    'message' => 'Không có quyền truy cập. Chỉ quản trị viên và nhân viên mới có thể xem thống kê.'
                ], 403);
            }

            $stats = [
                'total_users' => User::count(),
                'active_users' => User::where('status', 'active')->count(),
                'blocked_users' => User::where('status', 'blocked')->count(),
                'users_by_role' => [
                    'customers' => User::where('role', 'customer')->count(),
                    'staff' => User::where('role', 'staff')->count(),
                    'admins' => User::where('role', 'admin')->count()
                ],
                'recent_registrations' => [
                    'today' => User::whereDate('created_at', today())->count(),
                    'this_week' => User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                    'this_month' => User::whereMonth('created_at', now()->month)->count()
                ],
                'top_customers_by_points' => User::where('role', 'customer')
                    ->orderBy('points', 'desc')
                    ->limit(5)
                    ->select('id', 'fullname', 'email', 'points')
                    ->get()
            ];

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
            if (!in_array($request->user()->role, ['admin', 'staff'])) {
                return response([
                    'success' => false,
                    'message' => 'Không có quyền truy cập. Chỉ quản trị viên và nhân viên mới có thể xem thông tin người dùng.'
                ], 403);
            }

            $user = User::find($id);

            if (!$user) {
                return response([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            return response([
                'success' => true,
                'message' => 'Lấy thông tin người dùng thành công',
                'data' => [
                    'user' => $user
                ]
            ], 200);
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
            if ($request->user()->role !== 'admin') {
                return response([
                    'success' => false,
                    'message' => 'Không có quyền truy cập. Chỉ quản trị viên mới có thể cập nhật thông tin người dùng.'
                ], 403);
            }

            $user = User::find($id);
            if (!$user) {
                return response([
                    'success' => false,
                    'message' => 'Không tìm thấy người dùng'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'fullname' => 'sometimes|required|string|max:100',
                'email' => 'sometimes|required|email|max:100|unique:users,email,' . $id,
                'phone' => 'sometimes|nullable|string|max:20|unique:users,phone,' . $id,
                'address' => 'sometimes|nullable|string|max:255',
                'gender' => 'sometimes|nullable|in:male,female,other'
            ], [
                'fullname.required' => 'Họ tên không được để trống',
                'fullname.string' => 'Họ tên phải là chuỗi ký tự',
                'fullname.max' => 'Họ tên không được vượt quá 100 ký tự',
                'email.required' => 'Email không được để trống',
                'email.email' => 'Email không đúng định dạng',
                'email.max' => 'Email không được vượt quá 100 ký tự',
                'email.unique' => 'Email này đã được sử dụng',
                'phone.string' => 'Số điện thoại phải là chuỗi ký tự',
                'phone.max' => 'Số điện thoại không được vượt quá 20 ký tự',
                'phone.unique' => 'Số điện thoại này đã được sử dụng',
                'address.string' => 'Địa chỉ phải là chuỗi ký tự',
                'address.max' => 'Địa chỉ không được vượt quá 255 ký tự',
                'gender.in' => 'Giới tính phải là male, female hoặc other'
            ]);

            if ($validator->fails()) {
                return response([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update user (chỉ cho phép cập nhật thông tin cơ bản)
            $user->update($request->only(['fullname', 'email', 'phone', 'address', 'gender']));

            return response([
                'success' => true,
                'message' => 'Cập nhật người dùng thành công',
                'data' => [
                    'user' => $user->fresh()
                ]
            ], 200);
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
     */
    public function updateProfile(Request $request)
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
                'fullname.required' => 'Họ tên không được để trống',
                'fullname.string' => 'Họ tên phải là chuỗi ký tự',
                'fullname.max' => 'Họ tên không được vượt quá 100 ký tự',
                'email.required' => 'Email không được để trống',
                'email.email' => 'Email không đúng định dạng',
                'email.max' => 'Email không được vượt quá 100 ký tự',
                'email.unique' => 'Email này đã được sử dụng',
                'phone.string' => 'Số điện thoại phải là chuỗi ký tự',
                'phone.max' => 'Số điện thoại không được vượt quá 20 ký tự',
                'phone.unique' => 'Số điện thoại này đã được sử dụng',
                'address.string' => 'Địa chỉ phải là chuỗi ký tự',
                'address.max' => 'Địa chỉ không được vượt quá 255 ký tự',
                'gender.in' => 'Giới tính phải là male, female hoặc other',
                'avatar.image' => 'Tệp tải lên phải là hình ảnh',
                'avatar.mimes' => 'Ảnh đại diện chỉ chấp nhận định dạng: jpg, jpeg, png, gif',
                'avatar.max' => 'Kích thước ảnh đại diện tối đa là 2MB'
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
            // Update user profile
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
            // Check if user has admin role
            if ($request->user()->role !== 'admin') {
                return response([
                    'success' => false,
                    'message' => 'Không có quyền truy cập. Chỉ quản trị viên mới có thể khóa/mở khóa tài khoản.'
                ], 403);
            }

            $user = User::find($id);
            if (!$user) {
                return response([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Cannot block yourself
            if ($user->id === $request->user()->id) {
                return response([
                    'success' => false,
                    'message' => 'Bạn không thể khóa tài khoản của chính mình'
                ], 400);
            }

            // Toggle status
            $newStatus = $user->status === 'active' ? 'blocked' : 'active';
            $user->update(['status' => $newStatus]);

            // Revoke all tokens if blocking user
            if ($newStatus === 'blocked') {
                $user->tokens()->delete();
            }

            return response([
                'success' => true,
                'message' => $newStatus === 'active' ? 'Mở khóa tài khoản thành công' : 'Khóa tài khoản thành công',
                'data' => [
                    'user' => $user->fresh()
                ]
            ], 200);
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
            // Check if user has admin role
            if ($request->user()->role !== 'admin') {
                return response([
                    'success' => false,
                    'message' => 'Không có quyền truy cập. Chỉ quản trị viên mới có thể xóa người dùng.'
                ], 403);
            }

            $user = User::find($id);
            if (!$user) {
                return response([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Cannot delete yourself
            if ($user->id === $request->user()->id) {
                return response([
                    'success' => false,
                    'message' => 'Bạn không thể xóa tài khoản của chính mình'
                ], 400);
            }

            // Revoke all tokens before deletion
            $user->tokens()->delete();

            // Delete user
            $user->delete();

            return response([
                'success' => true,
                'message' => 'Xóa người dùng thành công'
            ], 200);
        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Xóa người dùng thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cho phép người dùng đã đăng nhập thay đổi mật khẩu của chính họ.
     */
    public function changePassword(Request $request)
    {
        // 1. Validation
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|confirmed|min:8',
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
