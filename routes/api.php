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

// Health check
Route::get('/health-check', fn() => response()->json(['status' => 'OK'], 200));

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
    Route::get('/user', fn(Request $request) => $request->user());

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
        Route::get('/', [UserController::class, 'index']);
        Route::get('/statistics', [UserController::class, 'statistics']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::patch('/{id}/toggle-status', [UserController::class, 'toggleStatus']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });

    // Points history routes
    Route::prefix('points')->group(function () {
        Route::get('/my-history', [PointsHistoryController::class, 'myHistory']);
        Route::get('/users/{userId}/history', [PointsHistoryController::class, 'userHistory']);
        Route::post('/add-points', [PointsHistoryController::class, 'addPoints']);
        Route::get('/history/{id}', [PointsHistoryController::class, 'show']);
    });
});

// Movie routes
Route::prefix('movies')->group(function () {
    Route::get('/list', [MovieController::class, 'index']);
    Route::get('/{id}', [MovieController::class, 'show'])->whereNumber('id');

    Route::middleware(['api.auth', 'role:admin,staff'])->group(function () {
        Route::get('/statistics', [MovieController::class, 'statistics']);
    });

    Route::middleware(['api.auth', 'role:admin'])->group(function () {
        Route::post('/', [MovieController::class, 'store']);
        Route::put('/{id}', [MovieController::class, 'update']);
        Route::patch('/{id}/status', [MovieController::class, 'changeStatus']);
        Route::delete('/{id}', [MovieController::class, 'destroy']);
    });
});

// Genre routes
Route::prefix('genres')->group(function () {
    Route::get('/public', [GenreController::class, 'indexPublic']);

    Route::middleware(['api.auth', 'role:admin'])->group(function () {
        Route::get('/', [GenreController::class, 'index']);
        Route::post('/', [GenreController::class, 'store']);
        Route::put('/{id}', [GenreController::class, 'update']);
        Route::delete('/{id}', [GenreController::class, 'destroy']);
    });
});

// Showtime routes
Route::prefix('showtimes')->group(function () {
    Route::get('/', [ShowtimeController::class, 'index']);
    Route::get('/rooms', [ShowtimeController::class, 'rooms']);
    Route::get('/dates/{roomId}', [ShowtimeController::class, 'showDates']);
    Route::get('/by-date', [ShowtimeController::class, 'getByDate']);
    Route::get('/by-date-language', [ShowtimeController::class, 'getByDateLanguage']);
    Route::get('/movie/{movieId}/full', [ShowtimeController::class, 'fullShowtimesByMovie']);
});

// Cinema routes
Route::prefix('cinemas')->group(function () {
    Route::get('/', [CinemaController::class, 'index']);
    Route::get('/statistics', [CinemaController::class, 'statistics']);
    Route::get('/{id}', [CinemaController::class, 'show']);
    Route::get('/{cinemaId}/rooms', [CinemaController::class, 'rooms']);
    Route::get('/{cinemaId}/showtimes', [CinemaController::class, 'showtimes']);
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
    Route::get('/', [ComboController::class, 'index']);
    Route::get('/{id}', [ComboController::class, 'show']);
});

// Tickets preview
Route::get('tickets/preview', [TicketController::class, 'preview']);

// Content routes
Route::prefix('contents')->group(function () {
    Route::get('/', [App\Http\Controllers\ContentController::class, 'index']);
    Route::get('/{id}', [App\Http\Controllers\ContentController::class, 'show']);
});

// Room routes
Route::prefix('rooms')->group(function () {
    Route::get('/', [RoomController::class, 'index']);
    Route::get('/{id}', [RoomController::class, 'show'])->whereNumber('id');
    Route::get('/cinema/{cinemaId}', [RoomController::class, 'byCinema'])->whereNumber('cinemaId');

    Route::middleware(['api.auth', 'role:admin,staff'])->group(function () {
        Route::get('/statistics', [RoomController::class, 'statistics']);
        Route::get('/statistics-by-cinema', [RoomController::class, 'statisticsByCinema']);
        Route::get('/statistics/cinema/{cinemaId}', [RoomController::class, 'statisticsByCinemaId'])->whereNumber('cinemaId');
    });

    Route::middleware(['api.auth', 'role:admin'])->group(function () {
        Route::post('/', [RoomController::class, 'store']);
        Route::put('/{id}', [RoomController::class, 'update']);
        Route::patch('/{id}/status', [RoomController::class, 'changeStatus']);
        Route::delete('/{id}', [RoomController::class, 'destroy']);
    });
});

// Seat routes
Route::prefix('seats')->group(function () {
    Route::get('/', [SeatController::class, 'index']);
    Route::get('/{id}', [SeatController::class, 'show'])->whereNumber('id');
    Route::get('/by-room/{roomId}', [SeatController::class, 'getSeatsByRoom'])->whereNumber('roomId');
    Route::get('/by-showtime/{showtimeId}', [SeatController::class, 'getSeatsByShowtime'])->whereNumber('showtimeId');

    Route::middleware(['api.auth', 'role:admin'])->group(function () {
        Route::post('/', [SeatController::class, 'store']);
        Route::put('/{id}', [SeatController::class, 'update']);
        Route::delete('/{id}', [SeatController::class, 'destroy']);
        Route::patch('/{id}/status', [SeatController::class, 'changeStatus'])->whereNumber('id');
    });
});

// Seat Reservation routes
Route::middleware(['api.auth', 'role:customer,admin,staff'])
    ->prefix('seat-reservations')
    ->group(function () {
        Route::post('/reserve', [SeatReservationController::class, 'reserveSeats'])->name('seat-reservations.reserve');
        Route::post('/confirm', [SeatReservationController::class, 'confirmBooking'])->name('seat-reservations.confirm');
        Route::post('/release', [SeatReservationController::class, 'releaseSeats'])->name('seat-reservations.release');
        Route::get('/my-reservations', [SeatReservationController::class, 'myReservations'])->name('seat-reservations.my');
        Route::get('/by-showtime/{showtimeId}', [SeatReservationController::class, 'getSeatsByShowtime'])
            ->whereNumber('showtimeId')
            ->name('seat-reservations.by-showtime');
    });
