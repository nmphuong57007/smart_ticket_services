<?php

namespace App\Http\Services\Payment;

class PaymentService
{
    public function createPaymentUrl($booking)
    {
        $vnp_Url        = config('vnpay.vnp_url');
        $vnp_Returnurl  = config('vnpay.vnp_returnurl');
        $vnp_TmnCode    = config('vnpay.vnp_tmncode');
        $vnp_HashSecret = config('vnpay.vnp_hashsecret');

        $vnp_TxnRef = uniqid();
        $vnp_OrderInfo = "Thanh toan don hang #" . $booking->id;
        $vnp_Amount = $booking->final_amount * 100;

        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_Command" => "pay",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_CurrCode" => "VND",
            "vnp_TxnRef" => $vnp_TxnRef,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_IpAddr" => request()->ip()
        ];

        ksort($inputData);

        $hashData = "";
        $query = "";

        foreach ($inputData as $key => $value) {
            $hashData .= $key . "=" . $value . "&";
            $query .= urlencode($key) . "=" . urlencode($value) . "&";
        }

        $hashData = rtrim($hashData, "&");
        $query = rtrim($query, "&");

        $vnp_SecureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        $paymentUrl = $vnp_Url . "?" . $query . "&vnp_SecureHash=" . $vnp_SecureHash;

        return [
            'payment_url' => $paymentUrl,
            'txn_ref' => $vnp_TxnRef
        ];
    }
}
