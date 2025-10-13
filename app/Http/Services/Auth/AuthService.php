<?php

namespace App\Http\Services\Auth;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Get current token ID from Authorization header
     */
    public function getCurrentTokenId(Request $request): ?int
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
    public function register(array $data): array
    {
        // Create the user
        $user = User::create([
            'fullname' => $data['fullname'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'gender' => $data['gender'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => 'customer', // Default role
            'points' => 0, // Default points
            'status' => 'active' // Default status
        ]);

        // Create API token for the user with IP tracking
        $newAccessToken = $user->createToken($data['device_name']);
        $token = $newAccessToken->plainTextToken;

        // Update token with IP address and user agent
        $newAccessToken->accessToken->update([
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null
        ]);

        return [
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
        ];
    }

    /**
     * Login user
     */
    public function login(array $credentials): array
    {
        // Find user by email
        $user = User::where('email', $credentials['email'])->first();

        // Check if user exists and password is correct
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email hoặc mật khẩu không chính xác.'],
            ]);
        }

        // Check if user is active
        if ($user->status !== 'active') {
            throw new \Exception('Tài khoản của bạn đã bị khóa. Vui lòng liên hệ hỗ trợ.');
        }

        // Create API token with IP tracking
        $newAccessToken = $user->createToken($credentials['device_name']);
        $token = $newAccessToken->plainTextToken;

        // Update token with IP address and user agent
        $newAccessToken->accessToken->update([
            'ip_address' => $credentials['ip_address'] ?? null,
            'user_agent' => $credentials['user_agent'] ?? null
        ]);

        return [
            'user' => [
                'id' => $user->id,
                'fullname' => $user->fullname,
                'avatar' => $user->avatar,
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
        ];
    }

    /**
     * Logout user (revoke current token)
     */
    public function logout(Request $request): bool
    {
        // Get the token from Authorization header
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            throw new \Exception('Tiêu đề xác thực không hợp lệ');
        }

        $token = substr($authHeader, 7); // Remove "Bearer " prefix

        // Find and delete the current token
        $accessToken = PersonalAccessToken::findToken($token);
        if ($accessToken) {
            $accessToken->delete();
            return true;
        }

        return false;
    }

    /**
     * Get user profile data
     */
    public function getUserProfile(User $user): array
    {
        return [
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
        ];
    }

    /**
     * Get all active sessions for user
     */
    public function getUserSessions(User $user, ?int $currentTokenId = null): array
    {
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

        return [
            'sessions' => $sessions,
            'total_sessions' => $sessions->count()
        ];
    }

    /**
     * Revoke a specific token/session
     */
    public function revokeUserSession(User $user, int $tokenId, ?int $currentTokenId = null): bool
    {
        // Don't allow revoking current token
        if ($tokenId == $currentTokenId) {
            throw new \Exception('Không thể huỷ phiên hiện tại. Vui lòng sử dụng đăng xuất.');
        }

        $deleted = $user->tokens()->where('id', $tokenId)->delete();
        return $deleted > 0;
    }

    /**
     * Revoke all tokens except current for user
     */
    public function revokeOtherSessions(User $user, ?int $currentTokenId = null): int
    {
        // Revoke all tokens except current
        return $user->tokens()->where('id', '!=', $currentTokenId)->delete();
    }

    /**
     * Revoke all tokens for user
     */
    public function revokeAllTokens(User $user): int
    {
        // Revoke all tokens for the user
        return $user->tokens()->delete();
    }
}