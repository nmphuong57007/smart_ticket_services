<?php

return [
    'vnp_url'       => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
    'vnp_tmncode'   => env('VNPAY_TMNCODE'),
    'vnp_hashsecret'=> env('VNPAY_HASHSECRET'),
    'vnp_returnurl' => env('VNPAY_RETURN_URL'),
];
