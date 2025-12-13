<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\Promotion;
use App\Models\BookingSeat;
use App\Models\Product;
use App\Models\BookingProduct;
use App\Http\Services\Seat\SeatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    protected SeatService $seatService;

    public function __construct(SeatService $seatService)
    {
        $this->seatService = $seatService;
    }

    /**
     * Tạo URL thanh toán VNPay (GIỮ NGUYÊN CHUẨN MAIN)
     */
    public function createVnpay(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id'
        ]);

        $booking = Booking::findOrFail($request->booking_id);
        $user    = $request->user();

        // ❗ Booking hết hạn
        if ($booking->created_at->diffInMinutes(now()) >= 10) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn hàng đã hết hạn thanh toán.'
            ], 410);
        }

        // === GIỮ NGUYÊN CHUẨN VNPay ===
        $vnp_TmnCode    = config('vnpay.vnp_tmncode');
        $vnp_HashSecret = config('vnpay.vnp_hashsecret');
        $vnp_Url        = config('vnpay.vnp_url');
        $vnp_Returnurl  = config('vnpay.vnp_returnurl');

        $vnp_TxnRef = uniqid();
        $vnp_Amount = $booking->final_amount * 100;
        $vnp_OrderInfo = "Thanh toan don hang " . $vnp_TxnRef;

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
            "vnp_TxnRef"    => $vnp_TxnRef,
        ];

        ksort($inputData);
        $query = http_build_query($inputData);
        $vnp_SecureHash = hash_hmac('sha512', $query, $vnp_HashSecret);
        $paymentUrl = $vnp_Url . "?" . $query . "&vnp_SecureHash=" . $vnp_SecureHash;
        // === END GIỮ NGUYÊN ===

        Payment::create([
            'booking_id'       => $booking->id,
            'user_id'          => $user->id,
            'method'           => 'vnpay',
            'amount'           => $booking->final_amount,
            'transaction_uuid' => $vnp_TxnRef,
            'pay_url'          => $paymentUrl,
            'status'           => 'pending',
        ]);

        return $paymentUrl;
    }

    /**
     * Callback VNPay
     */
    public function vnpayReturn(Request $request)
    {
        $vnp_HashSecret = config('vnpay.vnp_hashsecret');
        $vnp_SecureHash = $request->vnp_SecureHash;

        // === GIỮ NGUYÊN VERIFY HASH CỦA MAIN ===
        $inputData = $request->all();
        unset($inputData['vnp_SecureHash']);
        ksort($inputData);

        $i = 0;
        $hashData = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        if ($secureHash !== $vnp_SecureHash) {
            return redirect()->away("http://localhost:5173/check-payment?RspCode=97");
        }
        // === END VERIFY ===

        $txnRef = $request->vnp_TxnRef;
        $respCode = $request->vnp_ResponseCode;

        $payment = Payment::where('transaction_uuid', $txnRef)->first();
        if (!$payment) {
            return redirect()->away("http://localhost:5173/check-payment?RspCode=404");
        }

        $booking = $payment->booking;

        // Double callback
        if ($payment->status === 'success') {
            return redirect()->away("http://localhost:5173/check-payment?RspCode=00&Order={$txnRef}");
        }

        // Booking hết hạn → trả ghế
        if ($booking->created_at->diffInMinutes(now()) >= 10) {

            $seatIds = BookingSeat::where('booking_id', $booking->id)
                ->pluck('seat_id')
                ->toArray();

            $this->seatService->releaseSeats($seatIds);

            $payment->update(['status' => 'failed']);
            $booking->update([
                'payment_status' => Booking::PAYMENT_FAILED,
                'booking_status' => Booking::BOOKING_EXPIRED,
            ]);

            return redirect()->away("http://localhost:5173/check-payment?RspCode=48");
        }

        //        SUCCESS (00)
        if ($respCode == '00') {

            DB::transaction(function () use ($payment, $booking, $request) {

                // 0. Lock booking để tránh trạng thái bị chạy trùng trong transaction
                $bookingLocked = Booking::where('id', $booking->id)
                    ->lockForUpdate()
                    ->first();

                // Nếu đã paid rồi thì thôi (an toàn)
                if ($bookingLocked->payment_status === Booking::PAYMENT_PAID) {
                    return;
                }

                // 1. Cập nhật payment
                $payment->update([
                    'status'           => 'success',
                    'paid_at'          => now(),
                    'transaction_code' => $request->vnp_TransactionNo,
                    'bank_code'        => $request->vnp_BankCode,
                ]);

                // 2. Lấy danh sách ghế của booking
                $seatIds = BookingSeat::where('booking_id', $bookingLocked->id)
                    ->pluck('seat_id')
                    ->toArray();

                // 3. Chốt ghế
                $this->seatService->bookSeats($seatIds);

                        // 3.5 TRỪ KHO SẢN PHẨM (COMBO)
                        $bookingProducts = BookingProduct::where('booking_id', $bookingLocked->id)->get();

                if ($bookingProducts->isNotEmpty()) {
                    $productIds = $bookingProducts->pluck('product_id')->unique()->values();

                    // Lock các sản phẩm
                    $products = Product::whereIn('id', $productIds)
                        ->lockForUpdate()
                        ->get()
                        ->keyBy('id');

                    // Check tồn kho + active
                    foreach ($bookingProducts as $bp) {
                        $p = $products->get($bp->product_id);

                        if (!$p) {
                            throw new \Exception("Sản phẩm không tồn tại (ID {$bp->product_id}).");
                        }

                        if (!$p->is_active) {
                            throw new \Exception("Sản phẩm {$p->name} đang tạm ngưng bán.");
                        }

                        if ($p->stock < $bp->quantity) {
                            throw new \Exception("Sản phẩm {$p->name} không đủ tồn kho.");
                        }
                    }

                    // Trừ kho
                    foreach ($bookingProducts as $bp) {
                        $p = $products[$bp->product_id];
                        $p->stock = $p->stock - $bp->quantity;
                        $p->save();
                    }
                }

                // 4. Tạo ticket 1 booking = 1 QR
                $ticket = Ticket::firstOrCreate(
                    ['booking_id' => $bookingLocked->id],
                    ['qr_code' => null]
                );

                // Payload cho QR
                $payload = [
                    'ticket_id'   => $ticket->id,
                    'booking_id'  => $bookingLocked->id,
                    'user_id'     => $bookingLocked->user_id ?? null,
                    'showtime_id' => $bookingLocked->showtime_id ?? null,
                    'created_at'  => now()->toIso8601String(),
                ];

                $qrString = base64_encode(json_encode($payload));

                if (empty($ticket->qr_code)) {
                    $ticket->update(['qr_code' => $qrString]);
                }

                // 5. Cập nhật booking
                $bookingLocked->update([
                    'payment_status' => Booking::PAYMENT_PAID,
                    'booking_status' => Booking::BOOKING_PAID,
                ]);

                // 6. Promotion
                if ($bookingLocked->discount_code) {
                    $promo = Promotion::where('code', $bookingLocked->discount_code)->first();
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

        //        FAIL
        $seatIds = BookingSeat::where('booking_id', $booking->id)
            ->pluck('seat_id')
            ->toArray();

        $this->seatService->releaseSeats($seatIds);

        $payment->update(['status' => 'failed']);
        $booking->update([
            'payment_status' => Booking::PAYMENT_FAILED,
            'booking_status' => Booking::BOOKING_CANCELED,
        ]);

        return redirect()->away("http://localhost:5173/check-payment?RspCode={$respCode}");
    }
}
