<?php

namespace App\Http\Controllers;

use App\Models\ContentPost;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Services\ContentPost\ContentPostService;
use App\Http\Requests\ContentPostStoreRequest;
use App\Http\Requests\ContentPostUpdateRequest;
use App\Http\Resources\ContentPostResource;

class ContentPostController extends Controller
{
    protected ContentPostService $service;

    public function __construct(ContentPostService $service)
    {
        $this->service = $service;
    }


    // Danh sách content_posts (banner, news, promotion) — PUBLIC
    public function index(Request $request)
    {
        $filters = $request->only([
            'type',
            'search',
            'is_published',
            'per_page'
        ]);

        $posts = $this->service->list($filters);

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách nội dung thành công',
            'data' => [
                'items' => ContentPostResource::collection($posts),
                'pagination' => [
                    'page'       => $posts->currentPage(),
                    'per_page'   => $posts->perPage(),
                    'total'      => $posts->total(),
                    'last_page'  => $posts->lastPage(),
                ]
            ]
        ], Response::HTTP_OK);
    }

    // Chi tiết content_post — PUBLIC
    public function show(int $id)
    {
        $post = ContentPost::with('creator')->find($id);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy nội dung'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lấy chi tiết nội dung thành công',
            'data' => new ContentPostResource($post)
        ], Response::HTTP_OK);
    }

    // Tạo bài viết — ADMIN
    public function store(ContentPostStoreRequest $request)
    {

        $user = auth('sanctum')->user();


        $post = $this->service->create($request->validated(), $user);

        return response()->json([
            'success' => true,
            'message' => 'Tạo nội dung thành công',
            'data' => new ContentPostResource($post)
        ], Response::HTTP_CREATED);
    }

    // Cập nhật bài viết — ADMIN
    public function update(ContentPostUpdateRequest $request, int $id)
    {
        $post = ContentPost::find($id);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy nội dung'
            ], Response::HTTP_NOT_FOUND);
        }

        $updated = $this->service->update($post, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật nội dung thành công',
            'data' => new ContentPostResource($updated)
        ], Response::HTTP_OK);
    }

    // Xóa bài viết — ADMIN
    public function destroy(int $id)
    {
        $post = ContentPost::find($id);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy nội dung'
            ], Response::HTTP_NOT_FOUND);
        }

        $this->service->delete($post);

        return response()->json([
            'success' => true,
            'message' => 'Xóa nội dung thành công'
        ], Response::HTTP_OK);
    }
}
