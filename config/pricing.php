<?php

return [

    // Giá cơ bản cho suất chiếu
    'base_price' => [

        // Thứ 2–6
        'weekday' => 80000,

        // Thứ 7–Chủ nhật
        'weekend' => 100000,
    ],

    // Hệ số giá theo loại ghế
    'seat_multiplier' => [
        'normal' => 1.0,  // giá thường
        'vip'    => 1.5,  // VIP = +50%
    ],
];
