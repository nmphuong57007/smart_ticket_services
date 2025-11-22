<?php

namespace App\Http\Services\Combo;

use App\Models\ComboItem;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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
    public function createCombo(array $data)
    {
        return DB::transaction(function () use ($data) {
            $combo = Product::create([
                'name' => $data['name'],
                'price' => $data['price'],
                'type' => 'combo',
                'category_id' => 3,
                'stock' => 0,
                'is_active' => 1,
            ]);

            foreach ($data['items'] as $item) {
                ComboItem::create([
                    'combo_id' => $combo->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity']
                ]);
            }

            return $combo->load('comboItems.product');
        });
    }

    public function updateCombo(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $combo = Product::where('type', 'combo')->find($id);
            if (!$combo) return null;

            $combo->update([
                'name' => $data['name'],
                'price' => $data['price'],
            ]);

            ComboItem::where('combo_id', $combo->id)->delete();

            foreach ($data['items'] as $item) {
                ComboItem::create([
                    'combo_id' => $combo->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity']
                ]);
            }

            return $combo->load('comboItems.product');
        });
    }

    public function deleteCombo(int $id)
    {
        $combo = Product::where('type', 'combo')->find($id);
        if (!$combo) return false;

        return DB::transaction(function () use ($combo) {
            ComboItem::where('combo_id', $combo->id)->delete();
            $combo->delete();
            return true;
        });
    }
}
