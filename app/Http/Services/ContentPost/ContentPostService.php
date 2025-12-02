<?php

namespace App\Http\Services\ContentPost;

use App\Models\ContentPost;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

class ContentPostService
{
    /**
     * Lấy danh sách content_posts (banner, news, promotion)
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return ContentPost::query()

            // --- AUTO PUBLISH + AUTO HIDE ---
            ->where(function ($q) use ($filters) {

                // Nếu admin cố tình lọc theo is_published → giữ nguyên behavior cũ
                if (isset($filters['is_published'])) {
                    return $q->where('is_published', $filters['is_published']);
                }

                // PUBLIC MODE (auto-publish & auto-hide)
                $q->where(function ($q2) {
                    $now = now();

                    $q2->where('is_published', true) // Admin bật thủ công
                        ->orWhere(function ($q3) use ($now) {
                            $q3->whereNotNull('published_at')
                                ->where('published_at', '<=', $now);
                        });
                })

                    // AUTO-HIDE: ẩn bài khi đến hạn unpublished_at
                    ->where(function ($q4) {
                        $now = now();

                        $q4->whereNull('unpublished_at')
                            ->orWhere('unpublished_at', '>=', $now);
                    });
            })

            // Filtering khác
            ->when($filters['type'] ?? null, fn($q, $v) => $q->where('type', $v))
            ->when($filters['search'] ?? null, fn($q, $v) => $q->where('title', 'like', "%{$v}%"))

            ->orderBy('published_at', 'desc')
            ->paginate($filters['per_page'] ?? 10);
    }


    /**
     * Upload file ảnh
     */
    private function uploadImage(UploadedFile $file): string
    {
        return $file->store('content_posts', 'public');
    }


    /**
     * Xóa ảnh cũ nếu tồn tại
     */
    private function deleteOldImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }


    /**
     * Tạo slug không bị trùng
     */
    private function generateSlug(string $title): string
    {
        return Str::slug($title) . '-' . time();
    }



    /**
     * Tạo mới content_post
     */
    public function create(array $data, $user): ContentPost
    {
        // Slug
        $data['slug'] = $this->generateSlug($data['title']);

        // Người tạo
        if ($user) {
            $data['created_by'] = $user->id;
            $data['created_by_name'] = $user->fullname ?? null;
        }

        // Upload ảnh nếu có
        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $data['image'] = $this->uploadImage($data['image']);
        }

        return ContentPost::create($data);
    }



    /**
     * Cập nhật content_post
     */
    public function update(ContentPost $post, array $data): ContentPost
    {
        // Nếu title thay đổi → regenerate slug
        if (isset($data['title']) && $data['title'] !== $post->title) {
            $data['slug'] = $this->generateSlug($data['title']);
        }

        // Nếu có ảnh mới
        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {

            // Xóa ảnh cũ nếu tồn tại
            $this->deleteOldImage($post->image);

            // Upload ảnh mới
            $data['image'] = $this->uploadImage($data['image']);
        }

        // Không cập nhật created_by / created_by_name
        unset($data['created_by'], $data['created_by_name']);

        $post->update($data);

        return $post;
    }



    /**
     * Xóa bài viết + ảnh khỏi storage
     */
    public function delete(ContentPost $post): bool
    {
        $this->deleteOldImage($post->image);
        return $post->delete();
    }
}
