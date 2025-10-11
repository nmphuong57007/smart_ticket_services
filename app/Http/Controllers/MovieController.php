<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MovieController extends Controller
{
    /**
     * Lấy danh sách phim với phân trang và bộ lọc
     */
    public function index(Request $request)
    {
        try {
            // Validate query parameters (chỉ lấy từ URL params)
            $validator = Validator::make($request->query(), [
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'search' => 'nullable|string|max:255',
                'status' => 'nullable|in:coming,showing,stopped',
                'genre' => 'nullable|string|max:100',
                'sort_by' => 'nullable|in:id,title,release_date,duration,created_at,status,genre,format',
                'sort_order' => 'nullable|in:asc,desc'
            ], [
                'page.integer' => 'Số trang phải là số nguyên',
                'page.min' => 'Số trang phải lớn hơn 0',
                'per_page.integer' => 'Số bản ghi mỗi trang phải là số nguyên',
                'per_page.min' => 'Số bản ghi mỗi trang phải lớn hơn 0',
                'per_page.max' => 'Số bản ghi mỗi trang không được vượt quá 100',
                'search.string' => 'Từ khóa tìm kiếm phải là chuỗi ký tự',
                'search.max' => 'Từ khóa tìm kiếm không được vượt quá 255 ký tự',
                'status.in' => 'Trạng thái phim phải là một trong: coming, showing, stopped',
                'genre.string' => 'Thể loại phải là chuỗi ký tự',
                'genre.max' => 'Thể loại không được vượt quá 100 ký tự',
                'sort_by.in' => 'Trường sắp xếp phải là một trong: id, title, release_date, duration, created_at, status, genre, format',
                'sort_order.in' => 'Hướng sắp xếp phải là asc hoặc desc'
            ]);

            if ($validator->fails()) {
                return response([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Sử dụng các phương thức có sẵn của Eloquent
            $sortBy = $request->query('sort_by', 'id');
            $sortOrder = $request->query('sort_order', 'desc');

            // Xây dựng query với filters và sorting
            $movies = Movie::query()
                ->when($request->query('search'), fn($query, $search) => $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                }))
                ->when($request->query('status'), fn($query, $status) => $query->where('status', $status))
                ->when($request->query('genre'), fn($query, $genre) => $query->where('genre', 'like', "%{$genre}%"))
                ->when(true, function ($query) use ($sortBy, $sortOrder) {
                    // Apply sorting logic
                    if (in_array($sortBy, ['id', 'created_at']) && $sortOrder === 'desc') {
                        return $query->latest($sortBy);
                    } elseif (in_array($sortBy, ['id', 'created_at']) && $sortOrder === 'asc') {
                        return $query->oldest($sortBy);
                    } else {
                        return $query->orderBy($sortBy, $sortOrder);
                    }
                })
                ->paginate($request->query('per_page', 15));

            return response([
                'success' => true,
                'message' => 'Lấy danh sách phim thành công',
                'data' => [
                    'movies' => $movies->items(),
                    'pagination' => [
                        'current_page' => $movies->currentPage(),
                        'last_page' => $movies->lastPage(),
                        'per_page' => $movies->perPage(),
                        'total' => $movies->total(),
                        'from' => $movies->firstItem(),
                        'to' => $movies->lastItem()
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Lấy danh sách phim thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
