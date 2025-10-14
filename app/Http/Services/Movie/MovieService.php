<?php

namespace App\Http\Services\Movie;

use App\Models\Movie;

class MovieService
{
    /**
     * Get movies with pagination and filtering
     */
    public function getMovies(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $sortBy = $filters['sort_by'] ?? 'id';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        // Xây dựng query với filters và sorting
        return Movie::query()
            ->when($filters['search'] ?? null, fn($query, $search) => $query->where(function ($subQuery) use ($search) {
                $subQuery->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }))
            ->when($filters['status'] ?? null, fn($query, $status) => $query->where('status', $status))
            ->when($filters['genre'] ?? null, fn($query, $genre) => $query->where('genre', 'like', "%{$genre}%"))
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
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Find movie by ID
     */
    public function findMovieById(int $id): Movie
    {
        return Movie::findOrFail($id);
    }

    /**
     * Create new movie
     */
    public function createMovie(array $data): Movie
    {
        return Movie::create($data);
    }

    /**
     * Update movie
     */
    public function updateMovie(Movie $movie, array $data): Movie
    {
        $movie->update($data);
        return $movie->fresh();
    }

    /**
     * Delete movie
     */
    public function deleteMovie(Movie $movie): bool
    {
        return $movie->delete();
    }

    /**
     * Get movies by status
     */
    public function getMoviesByStatus(string $status): \Illuminate\Database\Eloquent\Collection
    {
        return Movie::where('status', $status)->get();
    }

    /**
     * Get movies by genre
     */
    public function getMoviesByGenre(string $genre): \Illuminate\Database\Eloquent\Collection
    {
        return Movie::where('genre', 'like', "%{$genre}%")->get();
    }

    /**
     * Search movies
     */
    public function searchMovies(string $search): \Illuminate\Database\Eloquent\Collection
    {
        return Movie::where(function ($query) use ($search) {
            $query->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        })->get();
    }

    /**
     * Get movie statistics
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
                ->get()
        ];
    }

    public function getMovieById(int $id): ?Movie
    {
        return Movie::find($id);
    }
}