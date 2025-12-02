<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Services\Product\ProductService;
use App\Http\Resources\ProductResource;

class ProductController extends Controller
{
    protected ProductService $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }


    /**
     * Lấy danh sách sản phẩm (Public)
     */
    public function index(Request $request)
    {
        $filters = [
            'type'       => $request->query('type'),
            'q'          => $request->query('q'),
            'min_price'  => $request->query('min_price'),
            'max_price'  => $request->query('max_price'),
            'in_stock'   => $request->query('in_stock', null),
            'sort_by'    => $request->query('sort_by'),
            'sort_order' => $request->query('sort_order'),
            'per_page'   => $request->query('per_page'),
        ];

        $products = $this->service->list($filters);

        return response([
            'success' => true,
            'message' => 'Lấy danh sách sản phẩm thành công.',
            'data' => [
                'products' => ProductResource::collection($products),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page'    => $products->lastPage(),
                    'total'        => $products->total(),
                ]
            ]
        ]);
    }


    /**
     * Chi tiết sản phẩm
     */
    public function show($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response([
                'success' => false,
                'message' => 'Không tìm thấy sản phẩm.'
            ], 404);
        }

        return response([
            'success' => true,
            'message' => 'Lấy thông tin sản phẩm thành công.',
            'data' => new ProductResource($product)
        ]);
    }


    /**
     * Tạo sản phẩm (Admin)
     */
    public function store(ProductStoreRequest $request)
    {
        $product = $this->service->create($request->validated());

        return response([
            'success' => true,
            'message' => 'Tạo sản phẩm thành công.',
            'data' => new ProductResource($product)
        ], 201);
    }


    /**
     * Cập nhật sản phẩm (Admin)
     */
    public function update(ProductUpdateRequest $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response([
                'success' => false,
                'message' => 'Không tìm thấy sản phẩm.'
            ], 404);
        }

        $updated = $this->service->update($product, $request->validated());

        return response([
            'success' => true,
            'message' => 'Cập nhật sản phẩm thành công.',
            'data' => new ProductResource($updated)
        ]);
    }


    /**
     * Xóa sản phẩm (Admin)
     */
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response([
                'success' => false,
                'message' => 'Không tìm thấy sản phẩm.'
            ], 404);
        }

        $this->service->delete($product);

        return response([
            'success' => true,
            'message' => 'Xóa sản phẩm thành công.'
        ]);
    }
}
