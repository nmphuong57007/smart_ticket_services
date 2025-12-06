<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected PaymentService $vnpay;

    public function __construct(PaymentService $vnpay)
    {
        $this->vnpay = $vnpay;
    }


    public function createVnpay(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id'
        ]);

        $booking = Booking::findOrFail($request->booking_id);
        $user = $request->user();

        $vnp_TmnCode    = config('vnpay.vnp_tmncode');
        $vnp_HashSecret = config('vnpay.vnp_hashsecret');
        $vnp_Url        = config('vnpay.vnp_url');
        $vnp_Returnurl  = config('vnpay.vnp_returnurl'); // http://localhost:8000/api/payment/vnpay/return

        // Tạo mã giao dịch
        $vnp_TxnRef = uniqid();
        $vnp_Amount = $booking->final_amount * 100;
        $vnp_OrderInfo = 'Thanh toan don hang ' . $vnp_TxnRef;

        $inputData = [
            "vnp_Version"   => "2.1.0",
            "vnp_TmnCode"   => $vnp_TmnCode,
            "vnp_Amount"    => $vnp_Amount,
            "vnp_Command"   => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode"  => "VND",
            "vnp_IpAddr"    => $request->ip(),
            "vnp_Locale"    => "vn",
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => "billpayment",
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef"    => $vnp_TxnRef
        ];

        ksort($inputData);
        $query = http_build_query($inputData);
        $vnp_SecureHash = hash_hmac('sha512', $query, $vnp_HashSecret);
        $vnp_Url .= '?' . $query . '&vnp_SecureHash=' . $vnp_SecureHash;
        // return $vnp_Url;
        $payment = Payment::create([
            'booking_id' => $booking->id,
            'user_id' => $user->id,
            'method' => 'vnpay',
            'amount' => $booking->final_amount,
            'transaction_uuid' => $vnp_TxnRef,
            'pay_url' => $vnp_Url,
            'status' => 'pending'
        ]);

        //   ksort($inputData);
        // $hashData = "";
        // $query = "";

        // foreach ($inputData as $key => $value) {
        //     if ($hashData == "") {
        //         $hashData = urlencode($key) . "=" . urlencode($value);
        //         $query = urlencode($key) . "=" . urlencode($value);
        //     } else {
        //         $hashData .= "&" . urlencode($key) . "=" . urlencode($value);
        //         $query .= "&" . urlencode($key) . "=" . urlencode($value);
        //     }
        // }

        // $vnp_SecureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        // $returnUrl = $vnp_Url . "?" . $query . "&vnp_SecureHash=" . $vnp_SecureHash;

        return $vnp_Url;
    }


    /** Callback từ VNPAY */
    public function vnpayReturn(Request $request)
    {
        $vnp_SecureHash = $request->vnp_SecureHash;
        $vnp_HashSecret = config('vnpay.vnp_hashsecret');
        // Lấy toàn bộ dữ liệu trả về từ VNPay
        $inputData = $request->all();

        // Lấy hash từ request
        unset($inputData['vnp_SecureHash']);

        // Sắp xếp dữ liệu theo key
        ksort($inputData);

        // Build chuỗi hash đúng chuẩn
        $i = 0;
        $hashData = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        // Tạo chữ ký để so sánh
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        // ❌ Sai chữ ký → redirect FE báo lỗi
        if ($secureHash !== $vnp_SecureHash) {
            return redirect()->away("http://localhost:5173/check-payment?RspCode=97&Message=InvalidSignature");
        }

        // Lấy transaction info
        $txnRef = $request->vnp_TxnRef;
        $responseCode = $request->vnp_ResponseCode;

        $payment = Payment::where('transaction_uuid', $txnRef)->first();

        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found']);
        }

        // ✔ Thành công
        if ($responseCode == '00') {
            $payment->update([
                'status' => 'success',
                'paid_at' => now(),
                'transaction_code' => $request->vnp_TransactionNo,
                'bank_code' => $request->vnp_BankCode
            ]);

            // cập nhật booking
            $payment->booking->update(['payment_status' => 'paid']);

            // Tăng used_count cho mã giảm giá (nếu có)
            $booking = $payment->booking;

            if ($booking->discount_code) {

                $promotion = \App\Models\Promotion::where('code', $booking->discount_code)->first();

                if ($promotion) {
                    // Chỉ tăng nếu mã vẫn còn hợp lệ và chưa hết lượt
                    if ($promotion->usage_limit === null || $promotion->used_count < $promotion->usage_limit) {

                        // Tăng lượt
                        $promotion->increment('used_count');

                        // Nếu đã dùng hết lượt → đổi trạng thái thành expired
                        if (
                            $promotion->usage_limit !== null &&
                            $promotion->used_count >= $promotion->usage_limit
                        ) {
                            $promotion->update(['status' => 'expired']);
                        }
                    }
                }
            }

            return redirect()->away("http://localhost:5173/check-payment?RspCode=00&Order={$txnRef}");
        }

        $payment->update([
            'status' => 'failed'
        ]);

        // ❌ Thất bại
        return redirect()->away("http://localhost:5173/check-payment?RspCode={$responseCode}&Order={$txnRef}");
    }
}
