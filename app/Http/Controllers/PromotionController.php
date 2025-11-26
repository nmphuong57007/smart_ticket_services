<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use App\Http\Services\Promotion\PromotionService;
use App\Http\Requests\StorePromotionRequest;
use App\Http\Requests\UpdatePromotionRequest;
use App\Http\Resources\PromotionResource;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    protected PromotionService $service;

    public function __construct(PromotionService $service)
    {
        $this->service = $service;
    }

    /**
     * Danh sách mã giảm giá có filter + pagination
     */
    public function index(Request $request)
    {
        $promotions = $this->service->getList($request->all());

        return response()->json([
            'success' => true,
            'data' => PromotionResource::collection($promotions),
            'pagination' => [
                'current_page' => $promotions->currentPage(),
                'per_page' => $promotions->perPage(),
                'last_page' => $promotions->lastPage(),
                'total' => $promotions->total(),
            ],
        ]);
    }

    /**
     * Tạo mã giảm giá mới
     */
    public function store(StorePromotionRequest $request)
    {
        $promotion = $this->service->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tạo mã giảm giá thành công.',
            'data' => new PromotionResource($promotion),
        ], 201);
    }

    /**
     * Cập nhật mã giảm giá
     */
    public function update(UpdatePromotionRequest $request, $id)
    {
        $promotion = Promotion::findOrFail($id);

        $updated = $this->service->update($promotion, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật mã giảm giá thành công.',
            'data' => new PromotionResource($updated),
        ]);
    }

    /**
     * Vô hiệu hoá (expire) mã giảm giá
     */
    public function destroy($id)
    {
        $promotion = Promotion::findOrFail($id);
        $this->service->disable($promotion);

        return response()->json([
            'success' => true,
            'message' => 'Mã giảm giá đã được vô hiệu hóa.',
        ]);
    }

    /**
     * Áp dụng mã giảm giá (public)
     */
    public function apply(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ], [
            'code.required' => 'Vui lòng nhập mã giảm giá.',
        ]);

        $result = $this->service->apply($request->code);

        return response()->json($result);
    }
}
