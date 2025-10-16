<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PointsHistoryController;

// Public routes (no authentication required)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
    Route::post('/reset-password', [ResetPasswordController::class, 'reset']);
    Route::post('/change-password', [UserController::class, 'changePassword']);
});

// Protected routes (authentication required)
Route::middleware('api.auth')->group(function () {
    // User profile routes
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::prefix('auth')->group(function () {
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::post('/profile', [UserController::class, 'updateProfile']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/change-password', [UserController::class, 'changePassword']);


        // Session management
        Route::get('/sessions', [AuthController::class, 'getSessions']);
        Route::post('/revoke-session', [AuthController::class, 'revokeSession']);
        Route::post('/revoke-other-sessions', [AuthController::class, 'revokeOtherSessions']);
        Route::post('/revoke-all-tokens', [AuthController::class, 'revokeAllTokens']);
    });

    // User management routes (admin/staff only)
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']); // Get all users with pagination (admin/staff)
        Route::get('/statistics', [UserController::class, 'statistics']); // Get user statistics (admin/staff)
        Route::get('/{id}', [UserController::class, 'show']); // Get specific user (admin/staff only)
        Route::put('/{id}', [UserController::class, 'update']); // Update user (admin only)
        Route::patch('/{id}/toggle-status', [UserController::class, 'toggleStatus']); // Block/unblock user (admin only)
        Route::delete('/{id}', [UserController::class, 'destroy']); // Delete user (admin only)
    });

    // Points history routes
    Route::prefix('points')->group(function () {
        Route::get('/my-history', [PointsHistoryController::class, 'myHistory']); // Lịch sử điểm của tôi
        Route::get('/users/{userId}/history', [PointsHistoryController::class, 'userHistory']); // Lịch sử điểm của user cụ thể (admin/staff)
        Route::post('/add-points', [PointsHistoryController::class, 'addPoints']); // Cộng điểm thủ công (admin)
        Route::get('/history/{id}', [PointsHistoryController::class, 'show']); // Chi tiết giao dịch điểm
    });
});



// Movie routes
Route::prefix('movies')->group(function () {
    Route::get('/list', [MovieController::class, 'index']);
});

Route::post('/auth/test', function (\Illuminate\Http\Request $r) {
    return response()->json(['ok' => true, 'method' => $r->method(), 'body' => $r->all()]);
});
