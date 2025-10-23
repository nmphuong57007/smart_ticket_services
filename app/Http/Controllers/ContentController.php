<?php

namespace App\Http\Controllers;

use App\Models\Content;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    /**
     * Hiển thị chi tiết 1 content theo id (trả về JSON).
     */
    public function index()
    {
        $contents = Content::orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $contents
        ]);
    }
    
    public function show($id)
    {
        // Tìm content theo ID
        $content = Content::find($id);

        if (!$content) {
            return response()->json([
                'status' => 'error',
                'message' => 'Content not found'
            ], 404);
        }

        // Trả dữ liệu về JSON
        return response()->json([
            'status' => 'success',
            'data' => $content
        ]);
    }
}