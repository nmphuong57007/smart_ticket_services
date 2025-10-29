<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PointsHistoryController;

use App\Http\Controllers\ShowtimeController;
use App\Http\Controllers\CinemaController;
use App\Http\Controllers\ComboController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\BookingController;

use App\Http\Controllers\DiscountController;
// Public routes (no authentication required)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
    Route::post('/reset-password', [ResetPasswordController::class, 'reset']);
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
        Route::get('/sessions',               [AuthController::class, 'getSessions']);
        Route::post('/revoke-session',        [AuthController::class, 'revokeSession']);
        Route::post('/revoke-other-sessions', [AuthController::class, 'revokeOtherSessions']);
        Route::post('/revoke-all-tokens',     [AuthController::class, 'revokeAllTokens']);
    });

    // User management routes (admin/staff only)
    Route::prefix('users')->group(function () {
        Route::get('/',                     [UserController::class, 'index']);        // Get all users with pagination (admin/staff)
        Route::get('/statistics',           [UserController::class, 'statistics']);   // Get user statistics (admin/staff)
        Route::get('/{id}',                 [UserController::class, 'show']);         // Get specific user (admin/staff only)
        Route::put('/{id}',                 [UserController::class, 'update']);       // Update user (admin only)
        Route::patch('/{id}/toggle-status', [UserController::class, 'toggleStatus']); // Block/unblock user (admin only)
        Route::delete('/{id}',              [UserController::class, 'destroy']);      // Delete user (admin only)
    });

    // Points history routes
    Route::prefix('points')->group(function () {
        Route::get('/my-history',             [PointsHistoryController::class, 'myHistory']);   // Lịch sử điểm của tôi
        Route::get('/users/{userId}/history', [PointsHistoryController::class, 'userHistory']); // Lịch sử điểm của user cụ thể (admin/staff)
        Route::post('/add-points',            [PointsHistoryController::class, 'addPoints']);   // Cộng điểm thủ công (admin)
        Route::get('/history/{id}',           [PointsHistoryController::class, 'show']);        // Chi tiết giao dịch điểm
    });
});

Route::prefix('movies')->group(function () {
    // Public
    Route::get('/list', [MovieController::class, 'index']); // Lấy danh sách phim (filter, paginate)
    Route::get('/{id}', [MovieController::class, 'show'])->whereNumber('id');  // Lấy chi tiết phim

    // Staff
    Route::middleware(['api.auth', 'role:admin,staff'])->group(function () {
        Route::get('/statistics', [MovieController::class, 'statistics']); // Thống kê phim
    });

    // Admin-only (toàn quyền)
    Route::middleware(['api.auth', 'role:admin'])->group(function () {
        Route::post('/',             [MovieController::class, 'store']);        // Thêm phim mới
        Route::put('/{id}',          [MovieController::class, 'update']);       // Cập nhật phim
        Route::patch('/{id}/status', [MovieController::class, 'changeStatus']); // Đổi trạng thái phim
        Route::delete('/{id}',       [MovieController::class, 'destroy']);      // Xóa phim
    });
});


// Showtime routes
Route::prefix('showtimes')->group(function () {
    Route::get('/',               [ShowtimeController::class, 'index']);     // Lấy danh sách lịch chiếu với filter & pagination
    Route::get('/rooms',          [ShowtimeController::class, 'rooms']);     // Lấy tất cả phòng có lịch chiếu
    Route::get('/dates/{roomId}', [ShowtimeController::class, 'showDates']); // Lấy các ngày chiếu của một phòng
    Route::get('/by-date',          [ShowtimeController::class, 'getByDate']);         // Lấy lịch chiếu theo ngày
    Route::get('/by-date-language', [ShowtimeController::class, 'getByDateLanguage']); // Lấy lịch chiếu theo ngày + ngôn ngữ
    Route::get('/movie/{movieId}/full', [ShowtimeController::class, 'fullShowtimesByMovie']); // full showtimes theo phim
});

// Cinema routes
Route::prefix('cinemas')->group(function () {
    Route::get('/',                     [CinemaController::class, 'index']);       // Lấy danh sách rạp
    Route::get('/statistics',           [CinemaController::class, 'statistics']);  // Thống kê tổng quan
    Route::get('/{id}',                 [CinemaController::class, 'show']);        // Chi tiết 1 rạp
    Route::get('/{cinemaId}/rooms',     [CinemaController::class, 'rooms']);       // Danh sách phòng của rạp
    Route::get('/{cinemaId}/showtimes', [CinemaController::class, 'showtimes']);   // Danh sách lịch chiếu của rạp
});

// Discount routes
Route::prefix('discounts')->middleware('api.auth')->group(function () {
    Route::get('/', [DiscountController::class, 'index'])->middleware('role:admin,staff');
    Route::post('/', [DiscountController::class, 'store'])->middleware('role:admin,staff');
    Route::put('/{id}', [DiscountController::class, 'update'])->middleware('role:admin,staff');
    Route::delete('/{id}', [DiscountController::class, 'destroy'])->middleware('role:admin');
    Route::post('/apply', [DiscountController::class, 'apply'])->middleware('role:admin,staff,customer');
});

// Combo routes
Route::prefix('combos')->group(function () {
    Route::get('/',     [ComboController::class, 'index']); // danh sách public
    Route::get('/{id}', [ComboController::class, 'show']); // chi tiết

});


// Public route xem thông tin vé trước khi đặt
Route::get('tickets/preview', [TicketController::class, 'preview']);

// Content routes
Route::prefix('contents')->group(function () {
    Route::get('/',     [App\Http\Controllers\ContentController::class, 'index']); // danh sách public
    Route::get('/{id}', [App\Http\Controllers\ContentController::class, 'show']); // chi tiết
});

Route::prefix('contents')->group(function () {
    Route::get('/',     [App\Http\Controllers\ContentController::class, 'index']);// danh sách public
    Route::get('/{id}', [App\Http\Controllers\ContentController::class, 'show']); // chi tiết
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/bookings', [BookingController::class, 'store']);
});