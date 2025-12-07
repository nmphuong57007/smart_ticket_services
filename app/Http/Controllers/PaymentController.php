<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\Promotion;
use App\Models\BookingSeat;
use App\Http\Services\Seat\SeatService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    protected SeatService $seatService;

    public function __construct(SeatService $seatService)
    {
        $this->seatService = $seatService;
    }

    /**
     * Tạo URL thanh toán VNPay
     */
    public function createVnpay(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id'
        ]);

        $booking = Booking::findOrFail($request->booking_id);
        $user    = $request->user();

        // ❗ Không cho thanh toán nếu booking hết hạn
        if ($booking->created_at->diffInMinutes(now()) >= 10) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn hàng đã hết hạn thanh toán.'
            ], 410);
        }

        // VNPay config
        $vnp_TmnCode    = config('vnpay.vnp_tmncode');
        $vnp_HashSecret = config('vnpay.vnp_hashsecret');
        $vnp_Url        = config('vnpay.vnp_url');
        $vnp_Returnurl  = config('vnpay.vnp_returnurl');

        $vnp_TxnRef = uniqid();
        $vnp_Amount = $booking->final_amount * 100;

        $inputData = [
            "vnp_Version"    => "2.1.0",
            "vnp_TmnCode"    => $vnp_TmnCode,
            "vnp_Amount"     => $vnp_Amount,
            "vnp_Command"    => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode"   => "VND",
            "vnp_IpAddr"     => $request->ip(),
            "vnp_Locale"     => "vn",
            "vnp_OrderInfo"  => "Thanh toan don hang " . $vnp_TxnRef,
            "vnp_OrderType"  => "billpayment",
            "vnp_ReturnUrl"  => $vnp_Returnurl,
            "vnp_TxnRef"     => $vnp_TxnRef
        ];

        ksort($inputData);
        $query = http_build_query($inputData);
        $secureHash = hash_hmac('sha512', $query, $vnp_HashSecret);

        $paymentUrl = $vnp_Url . '?' . $query . '&vnp_SecureHash=' . $secureHash;

        Payment::create([
            'booking_id'       => $booking->id,
            'user_id'          => $user->id,
            'method'           => 'vnpay',
            'amount'           => $booking->final_amount,
            'transaction_uuid' => $vnp_TxnRef,
            'pay_url'          => $paymentUrl,
            'status'           => 'pending'
        ]);

        return $paymentUrl;
    }

    /**
     * Callback từ VNPay
     */
    public function vnpayReturn(Request $request)
    {
        $vnp_HashSecret = config('vnpay.vnp_hashsecret');

        // Verify signature
        $inputData = $request->all();
        $vnp_SecureHash = $request->vnp_SecureHash;
        unset($inputData['vnp_SecureHash']);
        ksort($inputData);

        $hashData = urldecode(http_build_query($inputData));
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        if ($secureHash !== $vnp_SecureHash) {
            return redirect()->away("http://localhost:5173/check-payment?RspCode=97");
        }

        $txnRef = $request->vnp_TxnRef;
        $resp   = $request->vnp_ResponseCode;

        $payment = Payment::where('transaction_uuid', $txnRef)->first();
        if (!$payment) {
            return redirect()->away("http://localhost:5173/check-payment?RspCode=404");
        }

        $booking = $payment->booking;

        // ❗ CHẶN DOUBLE CALLBACK
        if ($payment->status === 'success' || $booking->booking_status === Booking::BOOKING_PAID) {
            return redirect()->away("http://localhost:5173/check-payment?RspCode=00&Order={$txnRef}");
        }

        // ❗ Nếu booking hết hạn → trả ghế + fail
        if ($booking->created_at->diffInMinutes(now()) >= 10) {

            $seatIds = BookingSeat::where('booking_id', $booking->id)
                ->pluck('seat_id')->toArray();

            $this->seatService->releaseSeats($seatIds);

            $booking->update([
                'booking_status' => Booking::BOOKING_EXPIRED,
                'payment_status' => Booking::PAYMENT_FAILED
            ]);

            $payment->update(['status' => 'failed']);

            return redirect()->away("http://localhost:5173/check-payment?RspCode=48");
        }

        // ===============================
        //        THANH TOÁN THÀNH CÔNG
        // ===============================
        if ($resp === '00') {

            DB::transaction(function () use ($booking, $payment, $request) {

                // Update payment
                $payment->update([
                    'status'           => 'success',
                    'paid_at'          => now(),
                    'transaction_code' => $request->vnp_TransactionNo,
                    'bank_code'        => $request->vnp_BankCode
                ]);

                // Lấy ghế từ booking_seats
                $seatIds = BookingSeat::where('booking_id', $booking->id)
                    ->pluck('seat_id')->toArray();

                // Chốt ghế
                $this->seatService->bookSeats($seatIds);

                // Tạo ticket
                foreach ($seatIds as $id) {
                    Ticket::create([
                        'booking_id' => $booking->id,
                        'seat_id'    => $id,
                        'qr_code'    => "TICKET-" . strtoupper(Str::random(10)),
                    ]);
                }

                // Update booking
                $booking->update([
                    'payment_status' => Booking::PAYMENT_PAID,
                    'booking_status' => Booking::BOOKING_PAID,
                ]);

                // Update promotion usage
                if ($booking->discount_code) {
                    $promo = Promotion::where('code', $booking->discount_code)->first();
                    if ($promo) {
                        $promo->increment('used_count');
                        if ($promo->usage_limit && $promo->used_count >= $promo->usage_limit) {
                            $promo->update(['status' => 'expired']);
                        }
                    }
                }
            });

            return redirect()->away("http://localhost:5173/check-payment?RspCode=00&Order={$txnRef}");
        }

        // ===============================
        //        THANH TOÁN FAIL
        // ===============================

        $seatIds = BookingSeat::where('booking_id', $booking->id)
            ->pluck('seat_id')->toArray();

        $this->seatService->releaseSeats($seatIds);

        $booking->update([
            'booking_status' => Booking::BOOKING_CANCELED,
            'payment_status' => Booking::PAYMENT_FAILED,
        ]);

        $payment->update(['status' => 'failed']);

        return redirect()->away("http://localhost:5173/check-payment?RspCode={$resp}");
    }
}
