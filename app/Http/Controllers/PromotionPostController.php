<?php

namespace App\Http\Controllers;

use App\Models\PromotionPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Routing\Controller; // Thêm nếu chưa có

class PromotionPostController extends Controller
{
    /**
     * 1. LẤY DANH SÁCH (Read All)
     * GET /admin/promotion-posts
     */
    public function index(Request $request)
    {
        // Lấy danh sách bài viết, phân trang và tải kèm thông tin người tạo
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
     * TẠO MỚI (Store)
     * POST /admin/promotion-posts
     */
    public function store(Request $request)
    {
        // 1. Validation
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image_file' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            'target_url' => 'nullable|url',
        ]);

        $data = $request->only(['title', 'description', 'target_url']);
        $data['created_by'] = Auth::id();
        $data['slug'] = Str::slug($request->title);

        // 3. Upload Ảnh Banner
        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('promotion_posts', 'public');
            $data['image_url'] = Storage::url($path);
        }

        // 4. Tạo bản ghi
        $post = PromotionPost::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Tạo bài viết khuyến mãi thành công.',
            'data' => $post
        ], 201);
    }

    /**
     * 2. XEM CHI TIẾT (Show)
     * GET /admin/promotion-posts/{id}
     */
    public function show($id)
    {
        // Tìm bài viết theo ID và tải thông tin người tạo
        $post = PromotionPost::with('creator:id,fullname')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Chi tiết bài viết khuyến mãi.',
            'data' => $post
        ]);
    }

    /**
     * CẬP NHẬT (Update)
     * PUT /admin/promotion-posts/{id}
     */
    public function update(Request $request, $id)
    {
        $post = PromotionPost::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'target_url' => 'nullable|url',
            'is_published' => 'boolean',
        ]);

        $data = $request->only(['title', 'description', 'target_url', 'is_published', 'published_at']);

        // 1. Xử lý Upload Ảnh Mới và Xóa ảnh cũ
        if ($request->hasFile('image_file')) {
            if ($post->image_url) {
                $oldPath = str_replace(Storage::url('/'), '', $post->image_url);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('image_file')->store('promotion_posts', 'public');
            $data['image_url'] = Storage::url($path);
        }

        // 2. Cập nhật Slug
        if ($request->has('title') && $post->title !== $request->title) {
            $data['slug'] = Str::slug($request->title);
        }

        $post->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật bài viết khuyến mãi thành công.',
            'data' => $post->fresh()
        ]);
    }

    /**
     * 3. XÓA (Destroy)
     * DELETE /admin/promotion-posts/{id}
     */
    public function destroy($id)
    {
        $post = PromotionPost::findOrFail($id);

        try {
            // 1. Xóa file ảnh khỏi Storage trước (quan trọng)
            if ($post->image_url) {
                $filePath = str_replace(Storage::url('/'), '', $post->image_url);
                Storage::disk('public')->delete($filePath);
            }

            // 2. Xóa bản ghi trong Database
            $post->delete();

            return response()->json([
                'success' => true,
                'message' => 'Xóa bài viết khuyến mãi thành công.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa bài viết.'
            ], 500);
        }
    }
}
