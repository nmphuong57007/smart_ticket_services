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

        return Movie::query()
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn($query, $status) => $query->where('status', $status))
            ->when($filters['genre'] ?? null, fn($query, $genre) => $query->where('genre', 'like', "%{$genre}%"))
            ->orderBy($sortBy, $sortOrder)
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Lấy phim theo ID (bắt lỗi nếu không thấy)
     */
    public function getMovieById(int $id): Movie
    {
        return Movie::findOrFail($id);
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
            return $movie->fresh();
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
        return Movie::where('status', $status)->get();
    }

    /**
     * Lấy phim theo thể loại
     */
    public function getMoviesByGenre(string $genre): Collection
    {
        return Movie::where('genre', 'like', "%{$genre}%")->get();
    }

    /**
     * Tìm kiếm phim
     */
    public function searchMovies(string $search): Collection
    {
        return Movie::where(function ($query) use ($search) {
            $query->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        })->get();
    }

    /**
     * Thống kê phim
     */
    public function getMovieStatistics(): array
    {
        return [
            'total_movies' => Movie::count(),
            'showing_movies' => Movie::where('status', 'showing')->count(),
            'coming_movies' => Movie::where('status', 'coming')->count(),
            'stopped_movies' => Movie::where('status', 'stopped')->count(),
            'movies_by_genre' => Movie::selectRaw('genre, COUNT(*) as count')
                ->groupBy('genre')
                ->pluck('count', 'genre')
                ->toArray(),
            'recent_movies' => Movie::latest('created_at')
                ->limit(5)
                ->select('id', 'title', 'status', 'release_date', 'created_at')
                ->get(),
        ];
    }
}
