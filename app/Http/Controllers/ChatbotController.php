<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OpenAI;
use App\Models\ChatLog;
use App\Models\Movie;
use App\Models\Cinema;
use App\Models\Showtime;

class ChatbotController extends Controller
{
    protected $openai;

    public function __construct()
    {
        $this->openai = OpenAI::client(env('OPENAI_API_KEY'));
    }

    public function chat(Request $request)
    {
        // Validation
        $request->validate([
            'message' => 'required|string|max:500',
            'user_id' => 'nullable|integer',
        ]);

        $message = $request->input('message');
        $userId = $request->input('user_id');

        // Check cache cho câu hỏi phổ biến
        $cacheKey = 'chatbot_' . md5($message);
        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            return response()->json($cached);
        }

        // Enrich message với context từ DB (ví dụ: danh sách phim)
        $context = $this->getContextFromDB();
        $fullPrompt = "Bạn là chatbot hỗ trợ đặt vé phim. Context: {$context}. User hỏi: {$message}";

        try {
            // Fake response for demo (bỏ comment để dùng thật)
            // $response = $this->openai->chat()->create([
            //     'model' => 'gpt-3.5-turbo',
            //     'messages' => [
            //         ['role' => 'system', 'content' => 'Bạn là trợ lý AI cho website đặt vé phim. Trả lời hữu ích, ngắn gọn.'],
            //         ['role' => 'user', 'content' => $fullPrompt],
            //     ],
            //     'max_tokens' => 150,
            // ]);
            // $aiResponse = $response['choices'][0]['message']['content'];

            // Fake response
            if (str_contains(strtolower($message), 'phim')) {
                $aiResponse = 'Hiện tại có phim Avengers, Spider-Man đang chiếu. Bạn muốn đặt vé phim nào?';
            } elseif (str_contains(strtolower($message), 'vé')) {
                $aiResponse = 'Để đặt vé, vui lòng chọn phim và suất chiếu trên website.';
            } else {
                $aiResponse = 'Xin chào! Tôi là chatbot hỗ trợ đặt vé phim. Bạn cần giúp gì?';
            }

            $suggestions = $this->getSuggestions($message);

            // Lưu log
            ChatLog::create([
                'user_id' => $userId,
                'user_message' => $message,
                'ai_response' => $aiResponse,
            ]);

            // Cache response (5 phút)
            Cache::put($cacheKey, ['response' => $aiResponse, 'suggestions' => $suggestions], 300);

            return response()->json(['response' => $aiResponse, 'suggestions' => $suggestions]);
        } catch (\Exception $e) {
            // Log lỗi để debug
            Log::error('Chatbot Error: ' . $e->getMessage());
            // Fallback
            return response()->json(['response' => 'Xin lỗi, tôi đang gặp sự cố. Vui lòng thử lại sau.'], 500);
        }
    }

    private function getContextFromDB()
    {
        // Lấy danh sách phim đang chiếu
        $movies = Movie::where('status', 'showing')->pluck('title')->implode(', ');

        // Lấy danh sách rạp
        $cinemas = Cinema::pluck('name')->implode(', ');

        // Lấy lịch chiếu gần nhất (ví dụ: trong 7 ngày tới)
        $upcomingShowtimes = Showtime::where('show_date', '>=', now()->toDateString())
            ->where('show_date', '<=', now()->addDays(7)->toDateString())
            ->with(['movie', 'room.cinema'])
            ->take(10)
            ->get()
            ->map(function ($showtime) {
                $datetime = $showtime->show_date . ' ' . $showtime->show_time;
                return $showtime->movie->title . ' tại ' . $showtime->room->cinema->name . ' lúc ' . \Carbon\Carbon::parse($datetime)->format('d/m H:i');
            })
            ->implode('; ');

        return "Website smart_ticket hỗ trợ đặt vé phim. Phim đang chiếu: {$movies}. Rạp: {$cinemas}. Lịch chiếu gần nhất: {$upcomingShowtimes}.";
    }

    private function getSuggestions($message)
    {
        $suggestions = [];

        if (str_contains(strtolower($message), 'phim')) {
            $suggestions = [
                'Lịch chiếu phim Avengers',
                'Giá vé phim Spider-Man',
                'Đặt vé online',
                'Phim sắp chiếu'
            ];
        } elseif (str_contains(strtolower($message), 'vé')) {
            $suggestions = [
                'Hướng dẫn đặt vé',
                'Thanh toán online',
                'Chính sách hoàn tiền',
                'Liên hệ hỗ trợ'
            ];
        } elseif (str_contains(strtolower($message), 'rạp') || str_contains(strtolower($message), 'cinema')) {
            $suggestions = [
                'Địa chỉ rạp gần nhất',
                'Lịch chiếu theo rạp',
                'Phòng chiếu VIP',
                'Dịch vụ tại rạp'
            ];
        } else {
            $suggestions = [
                'Phim đang chiếu',
                'Rạp chiếu phim',
                'Đặt vé online',
                'Lịch chiếu'
            ];
        }

        return $suggestions;
    }
}
