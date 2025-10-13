<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PointsHistoryController;

use App\Http\Controllers\ShowtimeController;

// Public routes (no authentication required)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});

// Protected routes (authentication required)
Route::middleware('api.auth')->group(function () {
    // User profile routes
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::prefix('auth')->group(function () {
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::post('/logout', [AuthController::class, 'logout']);

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


// Showtime routes - group prefix & middleware
Route::prefix('showtimes')->group(function () {
    Route::get('/', [ShowtimeController::class, 'index']);                   // Lấy danh sách lịch chiếu với filter & pagination
    Route::get('/rooms', [ShowtimeController::class, 'rooms']);              // Lấy tất cả phòng có lịch chiếu
    Route::get('/dates/{roomId}', [ShowtimeController::class, 'showDates']); // Lấy các ngày chiếu của một phòng
});
