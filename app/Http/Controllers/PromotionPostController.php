<?php

namespace App\Http\Controllers;

use App\Models\PromotionPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PromotionPostController extends Controller
{
    /**
     * 1. DANH SÁCH
     */
    public function index()
    {
        $posts = PromotionPost::with('creator:id,fullname')
            ->orderBy('published_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Danh sách bài viết khuyến mãi.',
            'data' => $posts
        ]);
    }

    /**
     * 2. TẠO MỚI
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
            'published_at' => 'nullable|date',
            'is_published' => 'nullable|boolean',
        ]);

        $data = $request->only(['title', 'description', 'published_at', 'is_published']);
        $data['created_by'] = Auth::id(); // Để quan hệ creator hoạt động
        $data['created_by_name'] = Auth::user()->fullname; // Lưu fullname


        $data['slug'] = Str::slug($request->title);

        // Upload ảnh → Trả về URL tuyệt đối
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('promotion_posts', 'public');

            // FULL URL
            $data['image_url'] = asset('storage/' . $path);
        }

        $post = PromotionPost::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Tạo bài viết khuyến mãi thành công.',
            'data' => $post
        ], 201);
    }

    /**
     * 3. CHI TIẾT
     */
    public function show($id)
    {
        $post = PromotionPost::with('creator:id,fullname')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Chi tiết bài viết.',
            'data' => $post
        ]);
    }

    /**
     * 4. CẬP NHẬT
     */
    public function update(Request $request, $id)
    {
        $post = PromotionPost::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'description' => 'nullable|string',
            'is_published' => 'boolean',
            'published_at' => 'nullable|date',
        ]);

        $data = $request->only(['title', 'description', 'is_published', 'published_at']);

        // XỬ LÝ ẢNH
        if ($request->hasFile('image_file')) {

            // ---- 1. XÓA ẢNH CŨ ----
            if (!empty($post->image_url)) {

                // URL => path thật trong storage
                // Ví dụ: http://127.0.0.1:8000/storage/promotion_posts/abc.png
                // lấy phần sau "/storage/"
                $relativePath = str_replace(url('storage') . '/', '', $post->image_url);

                // Xóa file
                Storage::disk('public')->delete($relativePath);
            }

            // ---- 2. LƯU ẢNH MỚI ----
            $path = $request->file('image_file')->store('promotion_posts', 'public');

            // Lưu URL đầy đủ để FE dùng được
            $data['image_url'] = url('storage/' . $path);
        }

        // Nếu đổi title thì đổi slug
        if ($request->title !== $post->title) {
            $data['slug'] = Str::slug($request->title);
        }

        $post->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật bài viết thành công.',
            'data' => $post->fresh()
        ]);
    }


    /**
     * 5. XÓA
     */
    public function destroy($id)
    {
        $post = PromotionPost::findOrFail($id);

        try {
            if ($post->image_url) {
                $old = str_replace(asset('storage') . '/', '', $post->image_url);
                Storage::disk('public')->delete($old);
            }

            $post->delete();

            return response()->json([
                'success' => true,
                'message' => 'Xóa bài viết thành công.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa bài viết.'
            ], 500);
        }
    }
}
