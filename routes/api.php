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
use App\Http\Controllers\SeatReservationController;
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
    Route::get('/', [ShowtimeController::class, 'index']);     // Lấy danh sách lịch chiếu với filter & pagination
    Route::get('/rooms', [ShowtimeController::class, 'rooms']);     // Lấy tất cả phòng có lịch chiếu
    Route::get('/dates/{roomId}', [ShowtimeController::class, 'showDates']); // Lấy các ngày chiếu của một phòng
    Route::get('/by-date', [ShowtimeController::class, 'getByDate']);         // Lấy lịch chiếu theo ngày
    Route::get('/by-date-language', [ShowtimeController::class, 'getByDateLanguage']); // Lấy lịch chiếu theo ngày + ngôn ngữ
    Route::get('/movie/{movieId}/full', [ShowtimeController::class, 'fullShowtimesByMovie']); // full showtimes theo phim
});

// Cinema routes
Route::prefix('cinemas')->group(function () {
    Route::get('/', [CinemaController::class, 'index']);       // Lấy danh sách rạp
    Route::get('/statistics', [CinemaController::class, 'statistics']);  // Thống kê tổng quan
    Route::get('/{id}', [CinemaController::class, 'show']);        // Chi tiết 1 rạp
    Route::get('/{cinemaId}/rooms', [CinemaController::class, 'rooms']);       // Danh sách phòng của rạp
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
// Room routes
Route::prefix('rooms')->group(function () {
    // Public routes (ai cũng xem được)
    Route::get('/', [RoomController::class, 'index']); // Lấy danh sách phòng (có filter, paginate)
    Route::get('/{id}', [RoomController::class, 'show'])->whereNumber('id'); // Chi tiết 1 phòng
    Route::get('/cinema/{cinemaId}', [RoomController::class, 'byCinema'])->whereNumber('cinemaId'); // Phòng theo rạp

    // Staff & Admin routes
    Route::middleware(['api.auth', 'role:admin,staff'])->group(function () {
        Route::get('/statistics', [RoomController::class, 'statistics']); // Thống kê tổng quan all phòng chiếu
        Route::get('/statistics-by-cinema', [RoomController::class, 'statisticsByCinema']); // Thống kê phòng chiếu theo rạp
        Route::get('/statistics/cinema/{cinemaId}', [RoomController::class, 'statisticsByCinemaId'])->whereNumber('cinemaId'); // Thống kê các phòng chiếu của một rạp cụ thể
    });

    // Admin-only routes (toàn quyền CRUD)
    Route::middleware(['api.auth', 'role:admin'])->group(function () {
        Route::post('/', [RoomController::class, 'store']);        // Tạo mới phòng
        Route::put('/{id}', [RoomController::class, 'update']);       // Cập nhật phòng
        Route::patch('/{id}/status', [RoomController::class, 'changeStatus']); // Đổi trạng thái phòng
        Route::delete('/{id}', [RoomController::class, 'destroy']);      // Xóa phòng
    });
});

// Seat routes
Route::prefix('seats')->group(function () {
    // Public (ai cũng có thể xem ghế)
    Route::get('/',     [SeatController::class, 'index']);           // Danh sách ghế (có filter room_id, type, status)
    Route::get('/{id}', [SeatController::class, 'show'])->whereNumber('id'); // Chi tiết 1 ghế

    // ghế theo phòng và theo lịch chiếu
    Route::get('/by-room/{roomId}',         [SeatController::class, 'getSeatsByRoom'])->whereNumber('roomId');
    Route::get('/by-showtime/{showtimeId}', [SeatController::class, 'getSeatsByShowtime'])->whereNumber('showtimeId');

    // Admin only (CRUD)
    Route::middleware(['api.auth', 'role:admin'])->group(function () {
        Route::post('/',       [SeatController::class, 'store']);      // Tạo ghế mới
        Route::put('/{id}',    [SeatController::class, 'update']);  // Cập nhật ghế
        Route::delete('/{id}', [SeatController::class, 'destroy']); // Xóa ghế

        // đổi trạng thái ghế
        Route::patch('/{id}/status', [SeatController::class, 'changeStatus'])->whereNumber('id');
    });
});

// Protected routes: phải login
Route::middleware(['api.auth', 'role:customer,admin,staff'])
    ->prefix('seat-reservations')->group(function () {
        Route::post('/reserve', [SeatReservationController::class, 'reserveSeats'])->name('seat-reservations.reserve');  // Giữ ghế tạm thời
        Route::post('/confirm', [SeatReservationController::class, 'confirmBooking'])->name('seat-reservations.confirm'); // Xác nhận đặt ghế
        Route::post('/release', [SeatReservationController::class, 'releaseSeats'])->name('seat-reservations.release');  // Hủy giữ ghế

        // Xem lịch sử đặt ghế của user
        Route::get('/my-reservations', [SeatReservationController::class, 'myReservations'])->name('seat-reservations.my');
        // Danh sách ghế theo suất chiếu
        Route::get('/by-showtime/{showtimeId}', [SeatReservationController::class, 'getSeatsByShowtime'])
            ->whereNumber('showtimeId')
            ->name('seat-reservations.by-showtime');
});

