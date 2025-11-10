<?php

namespace App\Http\Services\Movie;

use App\Models\Movie;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MovieService
{
    /**
     * Lấy danh sách phim (phân trang, lọc, sắp xếp)
     */
    public function getMovies(array $filters = []): LengthAwarePaginator
    {
        $sortBy = $filters['sort_by'] ?? 'id';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        return Movie::with('genres') // 🔹 load sẵn thể loại để tránh lỗi load() ở Controller
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn($query, $status) => $query->where('status', $status))
            ->when($filters['language'] ?? null, fn($query, $language) => $query->where('language', $language))
            ->when($filters['genre_id'] ?? null, function ($query, $genreId) {
                // 🔹 Lọc phim theo thể loại qua bảng pivot
                $query->whereHas('genres', fn($q) => $q->where('genres.id', $genreId));
            })
            ->orderBy($sortBy, $sortOrder)
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Lấy phim theo ID (bắt lỗi nếu không thấy)
     */
    public function getMovieById(int $id): Movie
    {
        return Movie::with('genres')->findOrFail($id);
    }

    /**
     * Tạo mới phim
     */
    public function createMovie(array $data): Movie
    {
        return DB::transaction(function () use ($data) {
            return Movie::create($data);
        });
    }

    /**
     * Cập nhật phim
     */
    public function updateMovie(Movie $movie, array $data): Movie
    {
        return DB::transaction(function () use ($movie, $data) {
            $movie->update($data);
            return $movie->fresh('genres');
        });
    }

    /**
     * Xóa phim
     */
    public function deleteMovie(Movie $movie): bool
    {
        return DB::transaction(function () use ($movie) {
            return $movie->delete();
        });
    }

    /**
     * Lấy phim theo trạng thái
     */
    public function getMoviesByStatus(string $status): Collection
    {
        return Movie::with('genres')->where('status', $status)->get();
    }

    /**
     * Lấy phim theo thể loại (nhiều-nhiều)
     */
    public function getMoviesByGenre(string $genreName): Collection
    {
        return Movie::with('genres')
            ->whereHas('genres', fn($q) => $q->where('genres.name', 'like', "%{$genreName}%"))
            ->get();
    }

    /**
     * Tìm kiếm phim
     */
    public function searchMovies(string $search): Collection
    {
        return Movie::with('genres')
            ->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->get();
    }

    /**
     * Thống kê phim
     */
    public function getMovieStatistics(): array
    {
        return [
            // 🔹 Thống kê tổng quan
            'total_movies'   => Movie::count(),
            'showing_movies' => Movie::where('status', 'showing')->count(),
            'coming_movies'  => Movie::where('status', 'coming')->count(),
            'stopped_movies' => Movie::where('status', 'stopped')->count(),

            // 🔹 Thống kê theo thể loại
            'movies_by_genre' => DB::table('movie_genre')
                ->join('genres', 'movie_genre.genre_id', '=', 'genres.id')
                ->select('genres.name', DB::raw('COUNT(movie_genre.movie_id) as count'))
                ->groupBy('genres.name')
                ->pluck('count', 'genres.name')
                ->toArray(),

            // 🔹 Toàn bộ phim (đầy đủ cột, có thể loại)
            'all_movies' => Movie::with('genres')
                ->orderBy('created_at', 'desc')
                ->get(),

            // 🔹 5 phim mới nhất (đầy đủ cột, có thể loại)
            'recent_movies' => Movie::with('genres')
                ->latest('created_at')
                ->limit(5)
                ->get(),
        ];
    }
}
