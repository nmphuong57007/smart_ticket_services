<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\MovieResource;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\MovieStoreRequest;
use App\Http\Requests\MovieUpdateRequest;
use App\Http\Services\Movie\MovieService;
use App\Http\Resources\MovieStatisticsResource;
use App\Http\Validator\Movie\MovieFilterValidator;

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
                'genre_id' => $request->query('genre_id'),
                'language' => $request->query('language'),
                'sort_by' => $request->query('sort_by', 'id'),
                'sort_order' => $request->query('sort_order', 'desc'),
                'per_page' => $request->query('per_page', 15),
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
                        'to' => $movies->lastItem(),
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

        // Upload poster
        if ($request->hasFile('poster')) {
            $path = $request->file('poster')->store('posters', 'public');
            $data['poster'] = $path; // chỉ lưu 'posters/...'
        }

        $data['language'] = $request->input('language');
        $data['end_date'] = $request->input('end_date');

        $movie = $this->movieService->createMovie($data);

        // Gán thể loại (checkbox)
        if ($request->has('genre_ids')) {
            $movie->genres()->sync($request->input('genre_ids'));
        }

        $movie->load('genres');

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

        // Upload lại poster nếu có
        if ($request->hasFile('poster')) {
            if ($movie->poster && Storage::disk('public')->exists($movie->poster)) {
                Storage::disk('public')->delete($movie->poster);
            }

            $path = $request->file('poster')->store('posters', 'public');
            $data['poster'] = $path; // chỉ lưu 'posters/...'
        }

        if ($request->has('language')) {
            $data['language'] = $request->input('language');
        }

        if ($request->has('end_date')) {
            $data['end_date'] = $request->input('end_date');
        }

        $updated = $this->movieService->updateMovie($movie, $data);

        // Cập nhật lại thể loại
        if ($request->has('genre_ids')) {
            $movie->genres()->sync($request->input('genre_ids'));
        }

        $updated->load('genres');

        return response([
            'success' => true,
            'message' => 'Cập nhật phim thành công',
            'data' => new MovieResource($updated)
        ]);
    }

    // Xóa phim (kèm ảnh và thể loại liên kết)
    public function destroy($id)
    {
        try {
            $movie = $this->movieService->getMovieById($id);
        } catch (\Exception $e) {
            return response(['success' => false, 'message' => 'Không tìm thấy phim'], 404);
        }

        // Xóa poster an toàn
        if ($movie->poster && Storage::disk('public')->exists($movie->poster)) {
            Storage::disk('public')->delete($movie->poster);
            Log::info("Đã xóa poster: {$movie->poster}");
        }

        // Gỡ thể loại liên kết
        $movie->genres()->detach();

        // Xóa phim
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

        $updated = $this->movieService->updateMovie($movie, [
            'status' => $request->input('status')
        ]);

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
        return response([
            'success' => true,
            'message' => 'Thống kê phim thành công',
            'data' => new MovieStatisticsResource($stats)
        ]);
    }
}
