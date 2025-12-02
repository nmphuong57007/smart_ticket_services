<?php

namespace App\Http\Services\Product;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    /**
     * Lọc + phân trang sản phẩm
     */
    public function list(array $filters)
    {
        return Product::query()

            // Lọc nhiều loại: combo,drink,food
            ->when($filters['type'] ?? null, function ($q, $type) {
                $q->whereIn('type', explode(',', $type));
            })

            // Tìm kiếm theo tên
            ->when($filters['q'] ?? null, function ($q, $keyword) {
                $q->where('name', 'like', "%$keyword%");
            })

            // Lọc giá
            ->when($filters['min_price'] ?? null, fn($q, $v) => $q->where('price', '>=', $v))
            ->when($filters['max_price'] ?? null, fn($q, $v) => $q->where('price', '<=', $v))

            // in_stock = 1 → stock > 0 (còn hàng)
            // in_stock = 0 → stock = 0 (hết hàng)
            // null → không lọc
            ->when(!is_null($filters['in_stock']), function ($q) use ($filters) {

                if ($filters['in_stock'] == '1') {
                    $q->where('stock', '>', 0);
                }

                if ($filters['in_stock'] == '0') {
                    $q->where('stock', '=', 0);
                }
            })

            // Sắp xếp
            ->orderBy($filters['sort_by'] ?? 'id', $filters['sort_order'] ?? 'asc')

            // Phân trang
            ->paginate($filters['per_page'] ?? 12);
    }

    /**
     * Tạo sản phẩm
     */
    public function create(array $data)
    {
        if (!empty($data['image'])) {
            $path = $data['image']->store('products', 'public');
            $data['image'] = 'storage/' . $path;
        }

        return Product::create($data);
    }

    /**
     * Cập nhật sản phẩm
     */
    public function update(Product $product, array $data)
    {
        if (!empty($data['image'])) {

            // Xóa ảnh cũ
            if ($product->image) {
                Storage::disk('public')->delete(str_replace('storage/', '', $product->image));
            }

            // Upload ảnh mới
            $path = $data['image']->store('products', 'public');
            $data['image'] = 'storage/' . $path;
        }

        $product->update($data);

        return $product;
    }

    /**
     * Xóa sản phẩm
     */
    public function delete(Product $product)
    {
        if ($product->image) {
            Storage::disk('public')->delete(str_replace('storage/', '', $product->image));
        }

        return $product->delete();
    }
}
