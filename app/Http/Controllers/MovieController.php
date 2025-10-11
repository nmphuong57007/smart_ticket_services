<?php

namespace App\Http\Controllers;

use App\Http\Services\Movie\MovieService;
use App\Http\Validator\Movie\MovieFilterValidator;
use App\Models\Movie;
use Illuminate\Http\Request;

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
}
