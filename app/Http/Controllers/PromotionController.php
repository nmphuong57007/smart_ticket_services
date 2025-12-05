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

    // Lấy danh sách mã giảm giá
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

    // Tạo mã giảm giá mới
    public function store(StorePromotionRequest $request)
    {
        $promotion = $this->service->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tạo mã giảm giá thành công.',
            'data' => new PromotionResource($promotion),
        ], 201);
    }

    // Cập nhật mã giảm giá
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

    // Admin vô hiệu hóa mã giảm giá (disabled)
    public function destroy($id)
    {
        $promotion = Promotion::findOrFail($id);

        // Gọi service để set status = disabled
        $this->service->disable($promotion);

        return response()->json([
            'success' => true,
            'message' => 'Mã giảm giá đã bị vô hiệu hóa.',
        ]);
    }

    // API áp dụng mã giảm giá (public)
    public function apply(Request $request)
    {
        $request->validate([
            'code'        => 'required|string',
            'movie_id'    => 'required|integer',
            'total_amount' => 'required|integer|min:0',
        ], [
            'code.required' => 'Vui lòng nhập mã giảm giá.',
            'movie_id.required' => 'Thiếu movie_id để áp mã theo phim.',
            'total_amount.required' => 'Thiếu tổng tiền để tính giảm giá.',
        ]);

        $result = $this->service->apply(
            $request->code,
            $request->movie_id,
            $request->total_amount
        );

        return response()->json($result);
    }
}
