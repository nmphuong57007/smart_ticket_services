<?php

return [
    'vnp_url' => env('VNPAY_URL'),
    'vnp_tmncode' => "4FC0OH07", //Mã định danh merchant kết nối (Terminal Id)
    'vnp_hashsecret' => "HA7LCGH11S2PM43UUPLOFB97BXS5PWHV", //Secret key
    'vnp_returnurl' => env('VNPAY_RETURN_URL'),
];
