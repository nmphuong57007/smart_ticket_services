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
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SeatController;
use App\Http\Controllers\SeatReservationController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\DiscountController;

// Kiểm tra hệ thống hoạt động
Route::get('/health-check', fn() => response()->json(['status' => 'OK'], 200));

// Các route công khai (không cần đăng nhập)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register'); // Đăng ký tài khoản
    Route::post('/login', [AuthController::class, 'login'])->name('login'); // Đăng nhập
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink']); // Quên mật khẩu
    Route::post('/reset-password', [ResetPasswordController::class, 'reset']); // Đặt lại mật khẩu
});

// Các route yêu cầu đăng nhập (middleware: api.auth)
Route::middleware('api.auth')->group(function () {

    // ✅ Lấy thông tin người dùng hiện tại
    Route::get('/user', fn(Request $request) => $request->user());

    // Nhóm route về tài khoản (AuthController & UserController)
    Route::prefix('auth')->group(function () {
        Route::get('/profile', [AuthController::class, 'profile']); // Xem thông tin cá nhân
        Route::post('/profile', [UserController::class, 'updateProfile']); // Cập nhật hồ sơ cá nhân
        Route::post('/logout', [AuthController::class, 'logout']); // Đăng xuất
        Route::post('/change-password', [UserController::class, 'changePassword']); // Đổi mật khẩu

        // 🔑 Quản lý phiên đăng nhập
        Route::get('/sessions', [AuthController::class, 'getSessions']); // Xem tất cả phiên đăng nhập
        Route::post('/revoke-session', [AuthController::class, 'revokeSession']); // Hủy 1 phiên cụ thể
        Route::post('/revoke-other-sessions', [AuthController::class, 'revokeOtherSessions']); // Hủy tất cả phiên khác
        Route::post('/revoke-all-tokens', [AuthController::class, 'revokeAllTokens']); // Hủy toàn bộ token
    });

    // Quản lý người dùng (admin/staff)
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']); // Danh sách người dùng
        Route::get('/statistics', [UserController::class, 'statistics']); // Thống kê người dùng
        Route::get('/{id}', [UserController::class, 'show']); // Xem chi tiết người dùng
        Route::put('/{id}', [UserController::class, 'update']); // Cập nhật người dùng
        Route::patch('/{id}/toggle-status', [UserController::class, 'toggleStatus']); // Khóa / mở tài khoản
        Route::delete('/{id}', [UserController::class, 'destroy']); // Xóa người dùng
    });

    // 💰 Quản lý điểm thưởng (PointsHistoryController)
    Route::prefix('points')->group(function () {
        Route::get('/my-history', [PointsHistoryController::class, 'myHistory']); // Lịch sử điểm của bản thân
        Route::get('/users/{userId}/history', [PointsHistoryController::class, 'userHistory']); // Lịch sử điểm của user khác (admin)
        Route::post('/add-points', [PointsHistoryController::class, 'addPoints']); // Thêm điểm thủ công
        Route::get('/history/{id}', [PointsHistoryController::class, 'show']); // Xem chi tiết 1 giao dịch điểm
    });
});

// Quản lý phim (MovieController)
Route::prefix('movies')->group(function () {
    Route::get('/list', [MovieController::class, 'index']); // Danh sách phim công khai
    Route::get('/{id}', [MovieController::class, 'show'])->whereNumber('id'); // Chi tiết phim

    Route::middleware(['api.auth', 'role:admin,staff'])->group(function () {
        Route::get('/statistics', [MovieController::class, 'statistics']); // Thống kê phim
    });

    Route::middleware(['api.auth', 'role:admin'])->group(function () {
        Route::post('/', [MovieController::class, 'store']); // Thêm phim
        Route::put('/{id}', [MovieController::class, 'update']); // Cập nhật phim
        Route::patch('/{id}/status', [MovieController::class, 'changeStatus']); // Thay đổi trạng thái (hiển thị/ẩn)
        Route::delete('/{id}', [MovieController::class, 'destroy']); // Xóa phim
    });
});

// Quản lý thể loại phim (GenreController)
Route::prefix('genres')->group(function () {
    Route::get('/public', [GenreController::class, 'indexPublic']); // Danh sách thể loại cho khách

    Route::middleware(['api.auth', 'role:admin'])->group(function () {
        Route::get('/', [GenreController::class, 'index']); // Danh sách đầy đủ (admin)
        Route::post('/', [GenreController::class, 'store']); // Thêm thể loại
        Route::put('/{id}', [GenreController::class, 'update']); // Cập nhật thể loại
        Route::delete('/{id}', [GenreController::class, 'destroy']); // Xóa thể loại
    });
});

// Quản lý lịch chiếu (ShowtimeController)
Route::prefix('showtimes')->group(function () {
    Route::get('/', [ShowtimeController::class, 'index']); // Danh sách lịch chiếu
    Route::get('/rooms', [ShowtimeController::class, 'rooms']); // Danh sách phòng
    Route::get('/dates/{roomId}', [ShowtimeController::class, 'showDates']); // Các ngày chiếu của phòng
    Route::get('/by-date', [ShowtimeController::class, 'getByDate']); // Lọc theo ngày
    Route::get('/by-date-language', [ShowtimeController::class, 'getByDateLanguage']); // Lọc theo ngày + ngôn ngữ
    Route::get('/movie/{movieId}/full', [ShowtimeController::class, 'fullShowtimesByMovie']); // Lịch chiếu đầy đủ của phim
});

// Quản lý rạp chiếu (CinemaController)
Route::prefix('cinemas')->group(function () {
    Route::get('/', [CinemaController::class, 'index']); // Danh sách rạp
    Route::get('/statistics', [CinemaController::class, 'statistics']); // Thống kê rạp
    Route::get('/{id}', [CinemaController::class, 'show']); // Chi tiết rạp
    Route::get('/{cinemaId}/rooms', [CinemaController::class, 'rooms']); // Danh sách phòng của rạp
    Route::get('/{cinemaId}/showtimes', [CinemaController::class, 'showtimes']); // Lịch chiếu tại rạp
});

// Quản lý khuyến mãi (DiscountController)
Route::prefix('discounts')->middleware('api.auth')->group(function () {
    Route::get('/', [DiscountController::class, 'index'])->middleware('role:admin,staff'); // Danh sách mã giảm giá
    Route::post('/', [DiscountController::class, 'store'])->middleware('role:admin,staff'); // Thêm mã giảm giá
    Route::put('/{id}', [DiscountController::class, 'update'])->middleware('role:admin,staff'); // Cập nhật mã
    Route::delete('/{id}', [DiscountController::class, 'destroy'])->middleware('role:admin'); // Xóa mã
    Route::post('/apply', [DiscountController::class, 'apply'])->middleware('role:admin,staff,customer'); // Áp dụng mã giảm giá
});

// Quản lý combo bắp nước (ComboController)
Route::prefix('combos')->group(function () {
    Route::get('/', [ComboController::class, 'index']); // Danh sách combo
    Route::get('/{id}', [ComboController::class, 'show']); // Chi tiết combo
});

// Xem trước vé (TicketController)
Route::get('tickets/preview', [TicketController::class, 'preview']); // Xem bản nháp vé trước khi mua

// Quản lý nội dung trang (ContentController)
Route::prefix('contents')->group(function () {
    Route::get('/', [App\Http\Controllers\ContentController::class, 'index']); // Danh sách nội dung
    Route::get('/{id}', [App\Http\Controllers\ContentController::class, 'show']); // Chi tiết nội dung
});

// Quản lý phòng chiếu (RoomController)
Route::prefix('rooms')->group(function () {
    Route::get('/', [RoomController::class, 'index']); // Danh sách phòng
    Route::get('/{id}', [RoomController::class, 'show'])->whereNumber('id'); // Chi tiết phòng
    Route::get('/cinema/{cinemaId}', [RoomController::class, 'byCinema'])->whereNumber('cinemaId'); // Phòng theo rạp

    Route::middleware(['api.auth', 'role:admin,staff'])->group(function () {
        Route::get('/statistics', [RoomController::class, 'statistics']); // Thống kê tổng thể phòng
        Route::get('/statistics-by-cinema', [RoomController::class, 'statisticsByCinema']); // Thống kê theo rạp
        Route::get('/statistics/cinema/{cinemaId}', [RoomController::class, 'statisticsByCinemaId'])->whereNumber('cinemaId');
    });

    Route::middleware(['api.auth', 'role:admin'])->group(function () {
        Route::post('/', [RoomController::class, 'store']); // Tạo phòng mới
        Route::put('/{id}', [RoomController::class, 'update']); // Cập nhật phòng
        Route::patch('/{id}/status', [RoomController::class, 'changeStatus']); // Thay đổi trạng thái phòng
        Route::delete('/{id}', [RoomController::class, 'destroy']); // Xóa phòng
    });
});

// Quản lý ghế (SeatController)
Route::prefix('seats')->group(function () {
    Route::get('/', [SeatController::class, 'index']); // Danh sách ghế
    Route::get('/{id}', [SeatController::class, 'show'])->whereNumber('id'); // Chi tiết ghế
    Route::get('/by-room/{roomId}', [SeatController::class, 'getSeatsByRoom'])->whereNumber('roomId'); // Lấy ghế theo phòng
    Route::get('/by-showtime/{showtimeId}', [SeatController::class, 'getSeatsByShowtime'])->whereNumber('showtimeId'); // Ghế theo lịch chiếu

    Route::middleware(['api.auth', 'role:admin'])->group(function () {
        Route::post('/', [SeatController::class, 'store']); // Tạo ghế mới
        Route::put('/{id}', [SeatController::class, 'update']); // Cập nhật ghế
        Route::delete('/{id}', [SeatController::class, 'destroy']); // Xóa ghế
        Route::patch('/{id}/status', [SeatController::class, 'changeStatus'])->whereNumber('id'); // Thay đổi trạng thái ghế
    });
});

// Đặt ghế (SeatReservationController)
Route::middleware(['api.auth', 'role:customer,admin,staff'])
    ->prefix('seat-reservations')
    ->group(function () {
        Route::post('/reserve', [SeatReservationController::class, 'reserveSeats'])->name('seat-reservations.reserve'); // Giữ ghế tạm thời
        Route::post('/confirm', [SeatReservationController::class, 'confirmBooking'])->name('seat-reservations.confirm'); // Xác nhận đặt vé
        Route::post('/release', [SeatReservationController::class, 'releaseSeats'])->name('seat-reservations.release'); // Hủy giữ ghế
        Route::get('/my-reservations', [SeatReservationController::class, 'myReservations'])->name('seat-reservations.my'); // Danh sách đặt ghế của user
        Route::get('/by-showtime/{showtimeId}', [SeatReservationController::class, 'getSeatsByShowtime'])
            ->whereNumber('showtimeId')
            ->name('seat-reservations.by-showtime'); // Lấy danh sách ghế theo lịch chiếu
    });
