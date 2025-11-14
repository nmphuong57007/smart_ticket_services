<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Xem danh sách tồn kho
     */
    public function index()
    {
        $products = Product::select('id', 'name', 'stock', 'price')
            ->orderBy('name')
            ->get();

        return response([
            'success' => true,
            'message' => 'Danh sách tồn kho sản phẩm.',
            'data' => $products
        ], 200);
    }

    /**
     * Điều chỉnh tồn kho (nhập / xuất / chỉnh sửa)
     */
    public function adjust(Request $request, $id)
    {
        $request->validate([
            'change' => 'required|integer|not_in:0',
            'type' => 'required|in:purchase,sale,adjustment,return,manual',
            'note' => 'nullable|string|max:255'
        ]);

        $product = Product::find($id);
        if (!$product) {
            return response([
                'success' => false,
                'message' => 'Sản phẩm không tồn tại.'
            ], 404);
        }

        DB::transaction(function () use ($product, $request) {
            $product->stock += $request->change;
            if ($product->stock < 0) {
                $product->stock = 0;
            }
            $product->save();

            InventoryTransaction::create([
                'product_id' => $product->id,
                'change' => $request->change,
                'type' => $request->type,
                'note' => $request->note,
                'created_by' => auth()->id() ?? null,
            ]);
        });

        return response([
            'success' => true,
            'message' => 'Cập nhật tồn kho thành công.',
            'data' => $product
        ], 200);
    }

    /**
     * Xem lịch sử tồn kho của 1 sản phẩm
     */
    public function history($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response([
                'success' => false,
                'message' => 'Sản phẩm không tồn tại.'
            ], 404);
        }

        $transactions = InventoryTransaction::where('product_id', $id)
            ->orderByDesc('created_at')
            ->get();

        return response([
            'success' => true,
            'message' => 'Lịch sử tồn kho sản phẩm.',
            'data' => [
                'product' => $product,
                'transactions' => $transactions
            ]
        ], 200);
    }
}
