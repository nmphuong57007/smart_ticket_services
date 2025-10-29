<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    // Danh sách mã
    public function index()
    {
        return response()->json(Discount::all());
    }

    // Thêm mã
    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|unique:promotions,code',
            'discount_percent' => 'required|integer|min:1|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $discount = Discount::create($data);
        return response()->json(['message' => 'Tạo mã giảm giá thành công', 'data' => $discount]);
    }

    // Cập nhật mã
    public function update(Request $request, $id)
    {
        $discount = Discount::findOrFail($id);
        $discount->update($request->only(['discount_percent', 'code', 'start_date', 'end_date', 'status']));
        return response()->json(['message' => 'Cập nhật thành công']);
    }

    // Xóa / vô hiệu hóa
    public function destroy($id)
    {
        $discount = Discount::findOrFail($id);
        $discount->update(['status' => 'expired']);
        return response()->json(['message' => 'Đã vô hiệu hóa mã']);
    }

    // Áp dụng mã khi người dùng nhập
    public function apply(Request $request)
    {
        $request->validate(['code' => 'required|string']);
        $discount = Discount::where('code', $request->code)->first();

        if (!$discount) {
            return response()->json(['success' => false, 'message' => 'Mã không tồn tại']);
        }

        if (!$discount->isValid()) {
            return response()->json(['success' => false, 'message' => 'Mã đã hết hạn hoặc không khả dụng']);
        }

        return response()->json([
            'success' => true,
            'discount_percent' => $discount->discount_percent,
            'message' => 'Áp dụng mã thành công',
        ]);
    }
}
