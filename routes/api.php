<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\RoomController;
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
use App\Http\Controllers\SeatController;
use App\Http\Controllers\GenreController;


use App\Http\Controllers\DiscountController;

Route::get(
    '/health-check',
    fn() => response()->json(['status' => 'OK'], 200)
);

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
        Route::get('/sessions', [AuthController::class, 'getSessions']);
        Route::post('/revoke-session', [AuthController::class, 'revokeSession']);
        Route::post('/revoke-other-sessions', [AuthController::class, 'revokeOtherSessions']);
        Route::post('/revoke-all-tokens', [AuthController::class, 'revokeAllTokens']);
    });

    // User management routes (admin/staff only)
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);        // Get all users with pagination (admin/staff)
        Route::get('/statistics', [UserController::class, 'statistics']);   // Get user statistics (admin/staff)
        Route::get('/{id}', [UserController::class, 'show']);         // Get specific user (admin/staff only)
        Route::put('/{id}', [UserController::class, 'update']);       // Update user (admin only)
        Route::patch('/{id}/toggle-status', [UserController::class, 'toggleStatus']); // Block/unblock user (admin only)
        Route::delete('/{id}', [UserController::class, 'destroy']);      // Delete user (admin only)
    });

    // Points history routes
    Route::prefix('points')->group(function () {
        Route::get('/my-history', [PointsHistoryController::class, 'myHistory']);   // Lịch sử điểm của tôi
        Route::get('/users/{userId}/history', [PointsHistoryController::class, 'userHistory']); // Lịch sử điểm của user cụ thể (admin/staff)
        Route::post('/add-points', [PointsHistoryController::class, 'addPoints']);   // Cộng điểm thủ công (admin)
        Route::get('/history/{id}', [PointsHistoryController::class, 'show']);        // Chi tiết giao dịch điểm
    });
});

Route::prefix('movies')->group(function () {
    // Public
    Route::get('/list', [MovieController::class, 'index']); // Lấy danh sách phim (filter, paginate)
    Route::get('/{id}', [MovieController::class, 'show'])->whereNumber('id');  // Lấy chi tiết phim
    Route::get('/{id}/showtimes', [MovieController::class, 'showtimesByMovie'])
    ->whereNumber('id'); // Lấy lịch chiếu theo phim

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

// Genre routes (gộp public + admin)
Route::prefix('genres')->group(function () {

    // Public: xem danh sách thể loại khả dụng (hiển thị checkbox chọn phim)
    Route::get('/public', [GenreController::class, 'indexPublic']);

    // Admin-only: quản lý thể loại
    Route::middleware(['api.auth', 'role:admin'])->group(function () {
        Route::get('/', [GenreController::class, 'index']);        // danh sách đầy đủ (kể cả ẩn)
        Route::post('/', [GenreController::class, 'store']);       // thêm
        Route::put('/{id}', [GenreController::class, 'update']);   // cập nhật
        Route::delete('/{id}', [GenreController::class, 'destroy']); // xóa
    });
});

// Showtime routes
Route::prefix('showtimes')->group(function () {

    // PUBLIC
    Route::get('/', [ShowtimeController::class, 'index']); // Lấy danh sách suất chiếu với filter & pagination
    Route::get('/rooms', [ShowtimeController::class, 'rooms']); // Lấy các phòng có suất chiếu
    Route::get('/dates/{roomId}', [ShowtimeController::class, 'showDates'])->whereNumber('roomId'); // Lấy danh sách ngày chiếu theo phòng
    Route::get('/statistics', [ShowtimeController::class, 'statistics']); // Thống kê lịch chiếu
    Route::get('/statistics/by-date', [ShowtimeController::class, 'statisticsByDate']); // Thống kê lịch chiếu theo ngày
    Route::get('/{id}/seats', [ShowtimeController::class, 'seats'])
    ->whereNumber('id'); // Lấy sơ đồ ghế của suất chiếu

    // ADMIN ONLY
    Route::middleware(['api.auth', 'role:admin'])->group(function () {
        Route::post('/', [ShowtimeController::class, 'store']); // Tạo suất chiếu
        Route::put('/{id}', [ShowtimeController::class, 'update'])->whereNumber('id'); // Cập nhật suất chiếu
        Route::delete('/{id}', [ShowtimeController::class, 'destroy'])->whereNumber('id'); // Xóa suất chiếu
    });
});

// Cinema (1 rạp duy nhất)
Route::prefix('cinema')->group(function () {
    Route::get('/', [CinemaController::class, 'cinema']);    // Public: xem thông tin rạp
    Route::get('/rooms', [CinemaController::class, 'rooms']);    // Public: danh sách phòng của rạp
    Route::get('/showtimes', [CinemaController::class, 'showtimes']);    // Public: lịch chiếu (có thể lọc theo ngày)
    // Staff & Admin: thống kê rạp
    Route::middleware(['api.auth', 'role:admin,staff'])->group(function () {
        Route::get('/statistics', [CinemaController::class, 'statistics']); // Thống kê rạp
    });
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
    Route::get('/', [ComboController::class, 'index']); // danh sách public
    Route::get('/{id}', [ComboController::class, 'show']); // chi tiết

});


// Public route xem thông tin vé trước khi đặt
Route::get('tickets/preview', [TicketController::class, 'preview']);

// Content routes
Route::prefix('contents')->group(function () {
    Route::get('/',     [App\Http\Controllers\ContentController::class, 'index']); // danh sách public
    Route::get('/{id}', [App\Http\Controllers\ContentController::class, 'show']); // chi tiết
});

// Room routes – CRUD đầy đủ cho mô hình 1 rạp duy nhất
Route::prefix('rooms')->group(function () {

    // PUBLIC: ai cũng xem được phòng
    Route::get('/', [RoomController::class, 'index']); // Danh sách phòng
    Route::get('/{id}', [RoomController::class, 'show'])->whereNumber('id'); // Chi tiết phòng

    // ADMIN: CRUD phòng
    Route::middleware(['api.auth', 'role:admin'])->group(function () {
        Route::post('/', [RoomController::class, 'store']); // Tạo phòng mới
        Route::put('/{id}', [RoomController::class, 'update'])->whereNumber('id'); // Cập nhật phòng
        Route::patch('/{id}/status', [RoomController::class, 'changeStatus'])->whereNumber('id'); // Đổi trạng thái phòng
        Route::patch('/{roomId}/seats/{seatCode}', [RoomController::class, 'updateSeatStatus'])->whereNumber('roomId'); // Cập nhật trạng thái ghế trong phòng
        Route::delete('/{id}', [RoomController::class, 'destroy'])->whereNumber('id'); // Xóa phòng
    });

    // ADMIN + STAFF: xem thống kê phòng
    Route::middleware(['api.auth', 'role:admin,staff'])->group(function () {
        Route::get('/statistics', [RoomController::class, 'statistics']);
    });
});



// Seat routes (seat per showtime)
Route::prefix('seats')->group(function () {

    // Public: xem ghế theo suất chiếu
    Route::get('/showtime/{showtimeId}', [SeatController::class, 'getSeatsByShowtime'])
        ->whereNumber('showtimeId');

    // Chi tiết ghế
    Route::get('/{id}', [SeatController::class, 'show'])->whereNumber('id');

    // Chỉ admin: đổi trạng thái seat
    Route::middleware(['api.auth', 'role:admin'])->group(function () {
        Route::patch('/{id}/status', [SeatController::class, 'changeStatus'])->whereNumber('id');
    });
});
