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
=======\\\\\\\\\
use App\Http\Services\Movie\MovieService;
use App\Http\Validator\Movie\MovieFilterValidator;
use App\Models\Movie;
use Illuminate\Container\Attributes\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB as FacadesDB;

class MovieController extends Controller
{
    protected MovieService $movieService;
    protected MovieFilterValidator $movieFilterValidator;

    public function __construct(MovieService $movieService, MovieFilterValidator $movieFilterValidator)
    {
        $this->movieService = $movieService;
        $this->movieFilterValidator = $movieFilterValidator;
    }

    /**
     * Lấy danh sách phim với phân trang và bộ lọc
     */
    public function index(Request $request)
    {
        try {
            // Validate query parameters (chỉ lấy từ URL params)
            $validationResult = $this->movieFilterValidator->validateWithStatus($request->query());
            if (!$validationResult['success']) {
                return response($validationResult, 422);
            }

            $filters = [
                'search' => $request->query('search'),
                'status' => $request->query('status'),
                'genre' => $request->query('genre'),
                'sort_by' => $request->query('sort_by', 'id'),
                'sort_order' => $request->query('sort_order', 'desc'),
                'per_page' => $request->query('per_page', 15)
            ];

            $movies = $this->movieService->getMovies($filters);

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

    /**
     * Lấy thông tin chi tiết của một phim
     */
    public function show($id)
    {
        $movie = Movie::find($id);

        if (!$movie) {
            return response()->json([
                'success' => false,
                'message' => 'Phim không tồn tại.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $movie
        ]);

    }
}
