<?php

namespace App\Services\Payment;

class PaymentService
{
    public function createPaymentUrl($booking)
    {
        // return "debugging";
        $vnp_Url        = config('vnpay.vnp_url');
        $vnp_Returnurl  = config('vnpay.vnp_returnurl');
        $vnp_TmnCode    = config('vnpay.vnp_tmncode');
        $vnp_HashSecret = config('vnpay.vnp_hashsecret');
        $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
        $vnp_Locale = "en-US";
        $startTime = date("YmdHis");
        $expire = date('YmdHis', strtotime('+15 minutes', strtotime($startTime)));

        $vnp_TxnRef = uniqid();
        $vnp_OrderInfo = "Thanh toan don hang " . $booking->id;
        $vnp_Amount = $booking->final_amount * 100;

        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => "vn",
            "vnp_OrderInfo" => "Thanh toan GD:" . $vnp_TxnRef,
            "vnp_OrderType" => "other",
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef,
            "vnp_ExpireDate" => $expire
        ];

        $inputData['vnp_BankCode'] = "INTCARD";
        // return $inputData;
        ksort($inputData);

        // // Build RAW string for hashing
        // $hashData = "";
        // foreach ($inputData as $key => $value) {
        //     $hashData .= $key . "=" . $value . "&";
        // }
        // $hashData = rtrim($hashData, "&");

        // // Create secure hash
        // $vnp_SecureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        // // Build query string: key=encode, value=raw (CHUẨN VNPAY)
        // $query = "";
        // foreach ($inputData as $key => $value) {
        //     $query .= urlencode($key) . "=" . urlencode($value) . "&";
        // }

        // $query .= "vnp_SecureHash=" . $vnp_SecureHash;
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $vnp_Url . "?" . $query;
        // return $vnp_HashSecret;
        if (isset($vnp_HashSecret)) {
            $vnpSecureHash =   hash_hmac('sha512', $hashdata, $vnp_HashSecret); //
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        }
        // header('Location: ' . $vnp_Url);
        // die();
        return [
            "payment_url" => $vnp_Url . "?" . $query,
            "txn_ref" => $vnp_TxnRef
        ];
    }
}
