<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    /* =========================
        USER FUNCTIONS
    ========================= */

    // User tạo review
    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa đăng nhập.',
            ], 401);
        }

        $request->validate([
            'movie_id' => 'required|exists:movies,id',
            'rating'   => 'required|integer|min:1|max:5',
            'comment'  => 'nullable|string',
        ]);

        /* =========================
            CHECK ĐÃ ĐẶT VÉ CHƯA
        ========================= */
        $hasBooking = Booking::where('user_id', $user->id)
            ->where('booking_status', 'paid')
            ->whereHas('showtime', function ($q) use ($request) {
                $q->where('movie_id', $request->movie_id);
            })
            ->exists();

        if (!$hasBooking) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn cần đặt vé và xem phim này trước khi review.',
            ], 403);
        }

        // Không cho review trùng phim
        if (
            Review::where('user_id', $user->id)
                ->where('movie_id', $request->movie_id)
                ->exists()
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã review phim này rồi.',
            ], 400);
        }

        $review = Review::create([
            'user_id'  => $user->id,
            'movie_id' => $request->movie_id,
            'rating'   => $request->rating,
            'comment'  => $request->comment,
            'status'   => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Gửi review thành công, chờ admin duyệt.',
            'data'    => $review,
        ]);
    }

    // User sửa review của mình
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $review = Review::findOrFail($id);

        if ($review->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền sửa review này.',
            ], 403);
        }

        $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $review->update([
            'rating'  => $request->rating,
            'comment' => $request->comment,
            'status'  => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật review thành công.',
        ]);
    }

    // User xóa review của mình
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $review = Review::findOrFail($id);

        if ($review->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xóa review này.',
            ], 403);
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa review thành công.',
        ]);
    }

    // Lấy review theo movie
    public function reviewsByMovie($movieId)
    {
        $reviews = Review::with('user:id,fullname,avatar')
            ->where('movie_id', $movieId)
            ->where('status', 'approved')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $reviews,
        ]);
    }

    /* =========================
        ADMIN FUNCTIONS
    ========================= */

    // Admin xem tất cả review
    public function adminIndex(Request $request)
    {
        $this->authorizeAdmin($request);

        $reviews = Review::with(['user:id,fullname', 'movie:id,title'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $reviews,
        ]);
    }

    // Admin duyệt review
    public function approve(Request $request, $id)
    {
        $this->authorizeAdmin($request);

        Review::findOrFail($id)->update(['status' => 'approved']);

        return response()->json([
            'success' => true,
            'message' => 'Đã duyệt review.',
        ]);
    }


    // Admin từ chối review
    public function reject(Request $request, $id)
    {
        $this->authorizeAdmin($request);

        Review::findOrFail($id)->update(['status' => 'rejected']);

        return response()->json([
            'success' => true,
            'message' => 'Đã từ chối review.',
        ]);
    }

    // Admin xóa review
    public function adminDestroy(Request $request, $id)
    {
        $this->authorizeAdmin($request);

        Review::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa review.',
        ]);
    }

    /* =========================
        HELPER
    ========================= */

    private function authorizeAdmin($request)
    {
        $user = $request->user();

        if (!$user || $user->role !== 'admin') {
            abort(403, 'Bạn không có quyền admin.');
        }
    }
}
