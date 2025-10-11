<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;


class MovieController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request) // <-- SỬA LỖI: Thêm Request $request vào đây
    {
        // 1. Khởi tạo Query Builder
        $query = Movie::query();

        // 2. Lọc theo trạng thái (status - Tùy chọn)
        // Ví dụ: Lấy phim đang chiếu
        if ($request->has('status') && in_array($request->status, ['coming', 'showing', 'stopped'])) {
            $query->where('status', $request->status);
        }

        // 3. Tìm kiếm theo tiêu đề (title - Tùy chọn)
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // 4. Phân trang (Pagination)
        // Mặc định lấy 15 phim mỗi trang
        $perPage = $request->get('per_page', 15);
        $movies = $query->latest() // Sắp xếp theo created_at mới nhất
            ->paginate($perPage);

        // 5. Trả về Response
        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách phim thành công.',
            'data' => $movies
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
