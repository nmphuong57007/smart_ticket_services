<?php

namespace App\Http\Services\Combo;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

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

    public function list($request)
    {
        return Product::query()
            ->search($request->keyword)
            ->priceRange($request->min_price, $request->max_price)
            ->paginate($request->per_page ?? 10);
    }

    public function create($data)
    {
        if (!empty($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
            $path = $data['image']->store('products', 'public');
            $data['image'] = 'storage/' . $path;
        } else {
            unset($data['image']);
        }
        return Product::create($data);
    }

    public function update(Product $product, $data)
    {
        if (isset($data['image'])) {

            if ($product->image) {
                Storage::disk('public')->delete(str_replace('storage/', '', $product->image));
            }

            $path = $data['image']->store('products', 'public');
            $data['image'] = 'storage/' . $path;
        }

        $product->update($data);

        return $product;
    }

    public function delete(Product $product)
    {
        if ($product->image) {
            Storage::disk('public')->delete(str_replace('storage/', '', $product->image));
        }

        return $product->delete();
    }
}
