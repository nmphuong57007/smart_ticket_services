<?php

namespace App\Http\Controllers;

use App\Http\Services\Movie\MovieService;
use App\Http\Validator\Movie\MovieFilterValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\MovieStoreRequest;
use App\Http\Requests\MovieUpdateRequest;
use App\Http\Resources\MovieResource;

class MovieController extends Controller
{
    protected MovieService $movieService;
    protected MovieFilterValidator $movieFilterValidator;

    public function __construct(MovieService $movieService, MovieFilterValidator $movieFilterValidator)
    {
        $this->movieService = $movieService;
        $this->movieFilterValidator = $movieFilterValidator;
    }

    // Lấy danh sách phim (filter + phân trang)
    public function index(Request $request)
    {
        try {
            $validationResult = $this->movieFilterValidator->validateWithStatus($request->query());
            if (!$validationResult['success']) {
                return response($validationResult, 422);
            }

            $filters = [
                'search' => $request->query('search'),
                'status' => $request->query('status'),
                'genre' => $request->query('genre'),
                'language' => $request->query('language'), // ✅ filter thêm theo ngôn ngữ
                'sort_by' => $request->query('sort_by', 'id'),
                'sort_order' => $request->query('sort_order', 'asc'),
                'per_page' => $request->query('per_page', 15)
            ];

            $movies = $this->movieService->getMovies($filters);

            return response([
                'success' => true,
                'message' => 'Lấy danh sách phim thành công',
                'data' => [
                    'movies' => MovieResource::collection($movies->items()),
                    'pagination' => [
                        'current_page' => $movies->currentPage(),
                        'last_page' => $movies->lastPage(),
                        'per_page' => $movies->perPage(),
                        'total' => $movies->total(),
                        'from' => $movies->firstItem(),
                        'to' => $movies->lastItem()
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Lấy danh sách phim thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Lấy chi tiết phim
    public function show($id)
    {
        try {
            $movie = $this->movieService->getMovieById($id);
            return response([
                'success' => true,
                'data' => new MovieResource($movie)
            ]);
        } catch (\Exception $e) {
            return response(['success' => false, 'message' => 'Phim không tồn tại.'], 404);
        }
    }

    // Thêm phim mới
    public function store(MovieStoreRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('poster')) {
            $path = $request->file('poster')->store('posters', 'public');
            $data['poster'] = 'storage/' . $path;
        }

        // Gán ngôn ngữ và ngày kết thúc (nếu có)
        $data['language'] = $request->input('language');
        $data['end_date'] = $request->input('end_date');

        $movie = $this->movieService->createMovie($data);

        return response([
            'success' => true,
            'message' => 'Thêm phim mới thành công',
            'data' => new MovieResource($movie)
        ], 201);
    }

    // Cập nhật phim
    public function update(MovieUpdateRequest $request, $id)
    {
        try {
            $movie = $this->movieService->getMovieById($id);
        } catch (\Exception $e) {
            return response(['success' => false, 'message' => 'Không tìm thấy phim'], 404);
        }

        $data = $request->validated();

        if ($request->hasFile('poster')) {
            if ($movie->poster && str_contains($movie->poster, '/storage/')) {
                $filePath = str_replace('storage/', '', $movie->poster);
                if (Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }
            }

            $path = $request->file('poster')->store('posters', 'public');
            $data['poster'] = 'storage/' . $path;
        }

        // Cho phép cập nhật ngôn ngữ và ngày kết thúc
        if ($request->has('language')) {
            $data['language'] = $request->input('language');
        }

        if ($request->has('end_date')) {
            $data['end_date'] = $request->input('end_date');
        }

        $updated = $this->movieService->updateMovie($movie, $data);

        return response([
            'success' => true,
            'message' => 'Cập nhật phim thành công',
            'data' => new MovieResource($updated)
        ]);
    }

    // Xóa phim
    public function destroy($id)
    {
        try {
            $movie = $this->movieService->getMovieById($id);
        } catch (\Exception $e) {
            return response(['success' => false, 'message' => 'Không tìm thấy phim'], 404);
        }

        if ($movie->poster && str_contains($movie->poster, '/storage/')) {
            $filePath = str_replace('storage/', '', $movie->poster);
            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
        }

        $this->movieService->deleteMovie($movie);

        return response(['success' => true, 'message' => 'Xóa phim thành công']);
    }

    // Cập nhật trạng thái phim
    public function changeStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:coming,showing,stopped']);

        try {
            $movie = $this->movieService->getMovieById($id);
        } catch (\Exception $e) {
            return response(['success' => false, 'message' => 'Không tìm thấy phim'], 404);
        }

        $updated = $this->movieService->updateMovie($movie, ['status' => $request->input('status')]);

        return response([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công',
            'data' => new MovieResource($updated)
        ]);
    }

    // Thống kê phim
    public function statistics()
    {
        $stats = $this->movieService->getMovieStatistics();
        return response(['success' => true, 'data' => $stats]);
    }
}
