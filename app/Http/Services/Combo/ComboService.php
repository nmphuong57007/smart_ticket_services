<?php

namespace App\Http\Services\Combo;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ComboService
{
    /**
     * Lấy danh sách combo có filter + pagination
     *
     * @param array $filters
     * @return LengthAwarePaginator
     */

    public function getCombos(array $filters = []): LengthAwarePaginator
    {
        $filters = array_merge([
            'q' => null,
            'min_price' => null,
            'max_price' => null,
            'in_stock' => null,
            'sort_by' => 'id',
            'sort_order' => 'asc',
            'per_page' => 12,
        ], $filters);

        $query = Product::combos()
            ->search($filters['q'] ?? null)
            ->priceRange($filters['min_price'] ?? null, $filters['max_price'] ?? null)
            ->inStock($filters['in_stock'] ?? null);

        $allowedSorts = ['id', 'name', 'price', 'stock'];
        $sortBy = in_array($filters['sort_by'] ?? '', $allowedSorts) ? $filters['sort_by'] : 'id';

        $sortOrder = $filters['sort_order'] ?? 'asc';
        $perPage = $filters['per_page'] ?? 12;

        return $query->orderBy($sortBy, $sortOrder)->paginate($perPage);
    }


    public function getComboById(int $id): ?Product
    {
        return Product::combos()->find($id);
    }
}
