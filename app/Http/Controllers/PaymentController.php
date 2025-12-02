<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Http\Services\Payment\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected PaymentService $vnpay;

    public function __construct(PaymentService $vnpay)
    {
        $this->vnpay = $vnpay;
    }

    /** Tạo URL thanh toán */
    public function createVnpay(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id'
        ]);

        $booking = Booking::findOrFail($request->booking_id);
        $user = $request->user();

        // Tạo URL thanh toán
        $vnp = $this->vnpay->createPaymentUrl($booking);

        // Lưu payment vào DB
        $payment = Payment::create([
            'booking_id' => $booking->id,
            'user_id' => $user->id,
            'method' => 'vnpay',
            'amount' => $booking->final_amount,
            'transaction_uuid' => $vnp['txn_ref'],
            'pay_url' => $vnp['payment_url'],
            'status' => 'pending'
        ]);

        return response()->json([
            'success' => true,
            'payment_url' => $payment->pay_url
        ]);
    }

    /** Callback từ VNPAY */
    public function vnpayReturn(Request $request)
    {
        $vnp_SecureHash = $request->vnp_SecureHash;

        $inputData = $request->except('vnp_SecureHash');
        ksort($inputData);

        $hashData = urldecode(http_build_query($inputData));
        $secureHash = hash_hmac('sha512', $hashData, config('vnpay.vnp_hashsecret'));

        if ($secureHash !== $vnp_SecureHash) {
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 400);
        }

        $txnRef = $request->vnp_TxnRef;
        $responseCode = $request->vnp_ResponseCode;

        $payment = Payment::where('transaction_uuid', $txnRef)->first();

        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found']);
        }

        if ($responseCode == "00") {
            $payment->update([
                'status' => 'success',
                'paid_at' => now(),
                'transaction_code' => $request->vnp_TransactionNo,
                'bank_code' => $request->vnp_BankCode
            ]);

            // cập nhật booking
            $payment->booking->update(['payment_status' => 'paid']);

            return view('payment.success');
        }

        $payment->update(['status' => 'failed']);

        return view('payment.failed');
    }
}
