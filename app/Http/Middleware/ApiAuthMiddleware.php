<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;

class ApiAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if Authorization header exists
        if (!$request->hasHeader('Authorization')) {
            return response([
                'success' => false,
                'message' => 'Thiếu tiêu đề xác thực',
                'error' => 'Thiếu tiêu đề Authorization. Vui lòng bao gồm: Authorization: Bearer TOKEN_CỦA_BẠN',
                'help' => [
                    'step_1' => 'Đăng nhập trước: POST /api/auth/login',
                    'step_2' => 'Lấy token từ kết quả đăng nhập',
                    'step_3' => 'Thêm token vào header: Authorization: Bearer TOKEN_CỦA_BẠN'
                ]
            ], 401);
        }

        // Get the Authorization header
        $authHeader = $request->header('Authorization');
        
        // Check if it's Bearer token format
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return response([
                'success' => false,
                'message' => 'Sai định dạng tiêu đề xác thực',
                'error' => 'Tiêu đề Authorization phải có định dạng: Bearer TOKEN_CỦA_BẠN',
                'received' => $authHeader
            ], 401);
        }

        // Extract token
        $token = substr($authHeader, 7); // Remove "Bearer " prefix
        
        if (empty($token)) {
            return response([
                'success' => false,
                'message' => 'Token trống',
                'error' => 'Vui lòng cung cấp token hợp lệ sau từ khóa Bearer'
            ], 401);
        }

        // Find token in database
        $accessToken = PersonalAccessToken::findToken($token);
        
        if (!$accessToken) {
            return response([
                'success' => false,
                'message' => 'Token không hợp lệ hoặc đã hết hạn',
                'error' => 'Token được cung cấp không hợp lệ hoặc đã bị thu hồi',
                'help' => 'Vui lòng đăng nhập lại để nhận token mới: POST /api/auth/login'
            ], 401);
        }

        // Check if user exists and is active
        $user = $accessToken->tokenable;
        
        if (!$user) {
            return response([
                'success' => false,
                'message' => 'Không tìm thấy người dùng',
                'error' => 'Người dùng liên kết với token này không còn tồn tại'
            ], 401);
        }

        if ($user->status !== 'active') {
            return response([
                'success' => false,
                'message' => 'Tài khoản bị khóa',
                'error' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ hỗ trợ.',
                'user_status' => $user->status
            ], 403);
        }

        // Update last used time
        $accessToken->forceFill(['last_used_at' => now()])->save();

        // Set authenticated user
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $next($request);
    }
}
