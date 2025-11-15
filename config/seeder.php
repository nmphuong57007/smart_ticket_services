<?php

return [
    // Số lượng mặc định cho các loại dữ liệu khi seed
    'cinemas' => env('SEED_CINEMAS', 30),
    'rooms_per_cinema' => env('SEED_ROOMS_PER_CINEMA', 5),
    'movies' => env('SEED_MOVIES', 100),
    'showtime_days' => env('SEED_SHOWTIME_DAYS', 7),
    'customers' => env('SEED_CUSTOMERS', 1000),
    'contents' => env('SEED_CONTENTS', 20),
    'combos' => env('SEED_COMBOS', 3),
    // Multiplier để dễ tăng giảm toàn bộ scale (1 = default, 10 = 10x)
    'multiplier' => env('SEED_SCALE_MULTIPLIER', 1),
    // Points history seeding
    'points_history_users' => env('SEED_POINTS_USERS', 50),
    'points_transactions_per_user' => env('SEED_POINTS_TX_PER_USER', 7),
];
