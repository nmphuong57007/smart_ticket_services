<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class ForgotPasswordController extends Controller
{
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $token = Str::random(60);

        // Lưu hoặc cập nhật token vào bảng reset
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => $token,
                'created_at' => Carbon::now()
            ]
        );

        // Gửi mail (ghi log nếu dùng MAIL_MAILER=log)
        $resetUrl = url("/reset-password?token={$token}");
        Mail::raw("Click vào link sau để đặt lại mật khẩu: {$resetUrl}", function ($message) use ($request) {
            $message->to($request->email)
                ->subject('Yêu cầu đặt lại mật khẩu');
        });

        return response()->json([
            'message' => 'Link đặt lại mật khẩu đã được gửi!',
            'token' => $token // chỉ để dev test
        ]);
    }
}
