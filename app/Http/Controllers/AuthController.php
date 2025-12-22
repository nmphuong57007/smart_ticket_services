<?php

namespace App\Http\Controllers;

use App\Http\Services\Auth\AuthService;
use App\Http\Validator\Auth\RegisterValidator;
use App\Http\Validator\Auth\LoginValidator;
use App\Http\Validator\Auth\RevokeSessionValidator;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected AuthService $authService;
    protected RegisterValidator $registerValidator;
    protected LoginValidator $loginValidator;
    protected RevokeSessionValidator $revokeSessionValidator;

    public function __construct(
        AuthService $authService,
        RegisterValidator $registerValidator,
        LoginValidator $loginValidator,
        RevokeSessionValidator $revokeSessionValidator
    ) {
        $this->authService = $authService;
        $this->registerValidator = $registerValidator;
        $this->loginValidator = $loginValidator;
        $this->revokeSessionValidator = $revokeSessionValidator;
    }

    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        try {
            // Validate the request
            $validationResult = $this->registerValidator->validateWithStatus($request->all());
            if (!$validationResult['success']) {
                return response([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validationResult['errors']
                ], 422);
            }

            $data = $request->all();
            $data['ip_address'] = $request->ip();
            $data['user_agent'] = $request->userAgent();

            $result = $this->authService->register($data);

            return response([
                'success' => true,
                'message' => 'Đăng ký tài khoản thành công',
                'data' => $result
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
            $validationResult = $this->loginValidator->validateWithStatus($request->all());
            if (!$validationResult['success']) {
                return response([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validationResult['errors']
                ], 422);
            }

            $credentials = $request->all();
            $credentials['ip_address'] = $request->ip();
            $credentials['user_agent'] = $request->userAgent();

            $result = $this->authService->login($credentials);

            if (isset($result['user']) && is_array($result['user'])) {

                if (empty($result['user']['avatar'])) {
                    $result['user']['avatar'] = null;
                } elseif (!str_starts_with($result['user']['avatar'], 'http')) {
                    // avatar lưu dạng path: avatars/xxx.jpg
                    $result['user']['avatar'] = asset('storage/' . $result['user']['avatar']);
                }
                // nếu đã là URL (seed) thì giữ nguyên
            }

            return response([
                'success' => true,
                'message' => 'Đăng nhập thành công',
                'data' => $result
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
            $this->authService->logout($request);

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
            $userData = $this->authService->getUserProfile($user);

            if (!empty($userData['avatar'])) {
                if (!str_starts_with($userData['avatar'], 'http')) {
                    $userData['avatar'] = asset('storage/' . $userData['avatar']);
                }
            } else {
                $userData['avatar'] = null;
            }

            return response([
                'success' => true,
                'message' => 'Lấy thông tin tài khoản thành công',
                'data' => [
                    'user' => $userData
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
            $currentTokenId = $this->authService->getCurrentTokenId($request);
            $sessionData = $this->authService->getUserSessions($user, $currentTokenId);

            return response([
                'success' => true,
                'message' => 'Lấy danh sách phiên đăng nhập thành công',
                'data' => $sessionData
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
            $validationResult = $this->revokeSessionValidator->validateWithStatus($request->all());
            if (!$validationResult['success']) {
                return response([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validationResult['errors']
                ], 422);
            }

            $user = $request->user();
            $currentTokenId = $this->authService->getCurrentTokenId($request);

            $success = $this->authService->revokeUserSession($user, $request->token_id, $currentTokenId);

            if ($success) {
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
            $user = $request->user();
            $currentTokenId = $this->authService->getCurrentTokenId($request);

            $deleted = $this->authService->revokeOtherSessions($user, $currentTokenId);

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
            $user = $request->user();
            $this->authService->revokeAllTokens($user);

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
