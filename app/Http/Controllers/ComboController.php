<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ComboRequest;
use App\Http\Services\Combo\ComboService;
use App\Http\Validator\Combo\ComboFilterValidator;
use App\Http\Resources\ComboResource;
use App\Models\Product;

class ComboController extends Controller
{
    protected ComboService $service;
    protected ComboFilterValidator $validator;

    public function __construct(ComboService $service, ComboFilterValidator $validator)
    {
        $this->service = $service;
        $this->validator = $validator;
    }

    public function index(Request $request)
    {
        $validation = $this->validator->validateWithStatus($request->query());
        if (!$validation['success']) {
            return response([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validation['errors'],
            ], 422);
        }

        $filters = [
            'q' => $request->query('q'),
            'min_price' => $request->query('min_price'),
            'max_price' => $request->query('max_price'),
            'in_stock' => $request->has('in_stock') ? (bool) $request->query('in_stock') : null,
            'per_page' => (int) $request->query('per_page', 12),
            'sort_by' => $request->query('sort_by'),
            'sort_order' => $request->query('sort_order'),
        ];

        $combos = $this->service->getCombos($filters);

        return response([
            'success' => true,
            'message' => 'Danh sách combo.',
            'data' => [
                'combos' => ComboResource::collection($combos->items()),
                'pagination' => [
                    'current_page' => $combos->currentPage(),
                    'last_page' => $combos->lastPage(),
                    'per_page' => $combos->perPage(),
                    'total' => $combos->total(),
                    'from' => $combos->firstItem(),
                    'to' => $combos->lastItem(),
                ],
            ],
        ], 200);
    }

    public function show(int $id)
    {
        $combo = $this->service->getComboById($id);
        if (!$combo) {
            return response([
                'success' => false,
                'message' => 'Combo không tồn tại'
            ], 404);
        }

        return response([
            'success' => true,
            'message' => 'Chi tiết combo',
            'data' => new ComboResource($combo)
        ], 200);
    }

    public function store(ComboRequest $request)
    {
        $product = $this->service->create($request->validated());
        return new ComboResource($product);
    }

    public function update(ComboRequest $request, $id)
    {
        $product = Product::findOrFail($id);
        $updated = $this->service->update($product, $request->validated());
        return new ComboResource($updated);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $this->service->delete($product);
        return response()->json(['message' => 'Product deleted']);
    }
}
