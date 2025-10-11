<?php

namespace App\Http\Controllers;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Get current token ID from Authorization header
     */
    private function getCurrentTokenId(Request $request)
    {
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $token = substr($authHeader, 7); // Remove "Bearer " prefix
        $accessToken = PersonalAccessToken::findToken($token);

        return $accessToken ? $accessToken->id : null;
    }

    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'fullname' => 'required|string|max:100',
                'email' => 'required|email|max:100|unique:users,email',
                'phone' => 'nullable|string|max:20|unique:users,phone',
                'address' => 'nullable|string|max:255',
                'gender' => 'nullable|in:male,female,other',
                'password' => 'required|string|min:8',
                'device_name' => 'required|string|max:255'
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
                'password.required' => 'Mật khẩu không được để trống',
                'password.string' => 'Mật khẩu phải là chuỗi ký tự',
                'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự',
                'device_name.required' => 'Tên thiết bị không được để trống',
                'device_name.string' => 'Tên thiết bị phải là chuỗi ký tự',
                'device_name.max' => 'Tên thiết bị không được vượt quá 255 ký tự'
            ]);

            if ($validator->fails()) {
                return response([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Create the user
            $user = User::create([
                'fullname' => $request->fullname,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'gender' => $request->gender,
                'password' => Hash::make($request->password),
                'role' => 'customer', // Default role
                'points' => 0, // Default points
                'status' => 'active' // Default status
            ]);

            // Create API token for the user with IP tracking
            $newAccessToken = $user->createToken($request->device_name);
            $token = $newAccessToken->plainTextToken;

            // Update token with IP address and user agent
            $newAccessToken->accessToken->update([
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return response([
                'success' => true,
                'message' => 'Đăng ký tài khoản thành công',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'fullname' => $user->fullname,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'address' => $user->address,
                        'gender' => $user->gender,
                        'role' => $user->role,
                        'points' => $user->points,
                        'status' => $user->status,
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at
                    ],
                    'token' => $token
                ]
            ], 201);

        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Đăng ký tài khoản thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string',
                'device_name' => 'required|string|max:255'
            ], [
                'email.required' => 'Email không được để trống',
                'email.email' => 'Email không đúng định dạng',
                'password.required' => 'Mật khẩu không được để trống',
                'password.string' => 'Mật khẩu phải là chuỗi ký tự',
                'device_name.required' => 'Tên thiết bị không được để trống',
                'device_name.string' => 'Tên thiết bị phải là chuỗi ký tự',
                'device_name.max' => 'Tên thiết bị không được vượt quá 255 ký tự'
            ]);

            if ($validator->fails()) {
                return response([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Find user by email
            $user = User::where('email', $request->email)->first();

            // Check if user exists and password is correct
            if (!$user || !Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['Email hoặc mật khẩu không chính xác.'],
                ]);
            }

            // Check if user is active
            if ($user->status !== 'active') {
                return response([
                    'success' => false,
                    'message' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ hỗ trợ.'
                ], 403);
            }

            // Create API token with IP tracking
            $newAccessToken = $user->createToken($request->device_name);
            $token = $newAccessToken->plainTextToken;

            // Update token with IP address and user agent
            $newAccessToken->accessToken->update([
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return response([
                'success' => true,
                'message' => 'Đăng nhập thành công',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'fullname' => $user->fullname,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'address' => $user->address,
                        'gender' => $user->gender,
                        'role' => $user->role,
                        'points' => $user->points,
                        'status' => $user->status,
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at
                    ],
                    'token' => $token
                ]
            ], 200);

        } catch (ValidationException $e) {
            return response([
                'success' => false,
                'message' => 'Thông tin đăng nhập không chính xác',
                'errors' => $e->errors()
            ], 401);
        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Đăng nhập thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout user (revoke current token)
     */
    public function logout(Request $request)
    {
        try {
            // Get the token from Authorization header
            $authHeader = $request->header('Authorization');
            if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
                return response([
                    'success' => false,
                    'message' => 'Tiêu đề xác thực không hợp lệ'
                ], 401);
            }

            $token = substr($authHeader, 7); // Remove "Bearer " prefix

            // Find and delete the current token
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken) {
                $accessToken->delete();
            }

            return response([
                'success' => true,
                'message' => 'Đăng xuất thành công'
            ], 200);

        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Đăng xuất thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get authenticated user profile
     */
    public function profile(Request $request)
    {
        try {
            $user = $request->user();

            return response([
                'success' => true,
                'message' => 'Lấy thông tin tài khoản thành công',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'fullname' => $user->fullname,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'address' => $user->address,
                        'gender' => $user->gender,
                        'avatar' => $user->avatar,
                        'role' => $user->role,
                        'points' => $user->points,
                        'status' => $user->status,
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Lấy thông tin tài khoản thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all active sessions for the authenticated user
     */
    public function getSessions(Request $request)
    {
        try {
            $user = $request->user();
            $currentTokenId = $this->getCurrentTokenId($request);

            // Get tokens sorted by ID desc (newest first)
            $sortedTokens = $user->tokens()->latest('id')->get();
            $sessions = $sortedTokens->map(fn($token) => [
                'id' => $token->id,
                'device_name' => $token->name,
                'ip_address' => $token->ip_address,
                'user_agent' => $token->user_agent,
                'device_info' => $token->device_info,
                'created_at' => $token->created_at,
                'last_used_at' => $token->last_used_at,
                'is_current' => $token->id === $currentTokenId
            ]);

            return response([
                'success' => true,
                'message' => 'Lấy danh sách phiên đăng nhập thành công',
                'data' => [
                    'sessions' => $sessions,
                    'total_sessions' => $sessions->count()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Lấy danh sách phiên đăng nhập thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revoke a specific token/session
     */
    public function revokeSession(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'token_id' => 'required|integer'
            ], [
                'token_id.required' => 'ID phiên không được để trống',
                'token_id.integer' => 'ID phiên phải là số nguyên'
            ]);

            if ($validator->fails()) {
                return response([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();
            $currentTokenId = $this->getCurrentTokenId($request);

            // Don't allow revoking current token
            if ($request->token_id == $currentTokenId) {
                return response([
                    'success' => false,
                    'message' => 'Không thể huỷ phiên hiện tại. Vui lòng sử dụng đăng xuất.'
                ], 400);
            }

            $deleted = $user->tokens()->where('id', $request->token_id)->delete();

            if ($deleted) {
                return response([
                    'success' => true,
                    'message' => 'Huỷ phiên đăng nhập thành công'
                ], 200);
            } else {
                return response([
                    'success' => false,
                    'message' => 'Không tìm thấy phiên hoặc đã bị huỷ'
                ], 404);
            }

        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Huỷ phiên đăng nhập thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revoke all tokens except current for the authenticated user
     */
    public function revokeOtherSessions(Request $request)
    {
        try {
            $currentTokenId = $this->getCurrentTokenId($request);

            // Revoke all tokens except current
            $deleted = $request->user()->tokens()->where('id', '!=', $currentTokenId)->delete();

            return response([
                'success' => true,
                'message' => "Đã đăng xuất khỏi {$deleted} thiết bị khác thành công"
            ], 200);

        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Huỷ các phiên khác thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revoke all tokens for the authenticated user
     */
    public function revokeAllTokens(Request $request)
    {
        try {
            // Revoke all tokens for the user
            $request->user()->tokens()->delete();

            return response([
                'success' => true,
                'message' => 'Huỷ tất cả phiên đăng nhập thành công'
            ], 200);

        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Huỷ tất cả phiên đăng nhập thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
