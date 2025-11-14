<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\InventoryTransaction; // Đã sử dụng Model chính xác
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth; // Đã thêm để đảm bảo Auth hoạt động

class InventoryController extends Controller
{
    /**
     * GET /admin/fb/inventory
     * 1. Xem danh sách tồn kho hiện tại của tất cả sản phẩm.
     */
    public function index(Request $request)
    {
        $products = Product::select('id', 'name', 'stock')
            ->orderBy('name', 'asc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Danh sách tồn kho hiện tại.',
            'data' => $products
        ]);
    }

    /**
     * POST /admin/fb/inventory/{id}/adjust
     * 2. Điều chỉnh tồn kho (nhập/xuất) cho một sản phẩm.
     */
    public function adjust(Request $request, $product_id)
    {
        // 1. Validation
        $request->validate([
            'change' => 'required|numeric|min:1',
            'type' => 'required|string|in:sale,purchase,adjustment,return',
            'reference' => 'nullable|string|max:255',
            'note' => 'nullable|string',
        ]);

        // 2. Bắt đầu Transaction để đảm bảo tính toàn vẹn
        DB::beginTransaction();

        try {
            $product = Product::findOrFail($product_id);

            $change_amount = $request->input('change');
            $operation_type = $request->input('type');

            $actual_change = ($operation_type === 'export') ? -$change_amount : $change_amount;

            // Kiểm tra tồn kho âm
            if ($operation_type === 'export' && ($product->stock + $actual_change) < 0) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Lỗi: Không đủ tồn kho để xuất.'], 400);
            }

            // 3. Cập nhật tồn kho hiện tại
            $product->increment('stock', $actual_change);

            // 4. Ghi lại bản ghi giao dịch (Transaction)
            InventoryTransaction::create([
                'product_id' => $product_id,
                'change' => $actual_change,
                'type' => $operation_type,
                'reference' => $request->input('reference'),
                'note' => $request->input('note'),
                // Ghi lại ID người dùng đã đăng nhập.
                'created_by' => Auth::check() ? Auth::id() : null,
                'created_at' => now(),
            ]);

            // 5. Commit Transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Điều chỉnh tồn kho thành công.',
                'new_stock' => $product->fresh()->stock
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            // Bạn có thể log $e->getMessage() ở đây để debug lỗi server
            return response()->json(['success' => false, 'message' => 'Lỗi điều chỉnh tồn kho.'], 500);
        }
    }

    /**
     * GET /admin/fb/inventory/{id}/history
     * 3. Xem lịch sử thay đổi tồn kho của một sản phẩm.
     */
    public function history(Request $request, $product_id)
    {
        // Truy vấn bảng giao dịch theo product_id
        $history = InventoryTransaction::where('product_id', $product_id)
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'message' => 'Lịch sử tồn kho của sản phẩm ID: ' . $product_id,
            'data' => $history
        ]);
    }
}
