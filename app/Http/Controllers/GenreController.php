<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Http\Request;
use App\Http\Resources\GenreResource;

class GenreController extends Controller
{
    /**
     * Danh sách toàn bộ thể loại (Admin)
     */
    public function index()
    {
        $genres = Genre::orderBy('name')->get();

        return response([
            'success' => true,
            'data' => GenreResource::collection($genres)
        ]);
    }

    /**
     * Thêm thể loại mới
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:genres,name',
        ]);

        $genre = Genre::create($data);

        return response([
            'success' => true,
            'message' => 'Thêm thể loại thành công',
            'data' => new GenreResource($genre)
        ], 201);
    }

    /**
     * Cập nhật thể loại
     */
    public function update(Request $request, $id)
    {
        $genre = Genre::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:100|unique:genres,name,' . $id,
            'is_active' => 'sometimes|boolean'
        ]);

        $genre->update($data);

        return response([
            'success' => true,
            'message' => 'Cập nhật thể loại thành công',
            'data' => new GenreResource($genre)
        ]);
    }

    /**
     * Xóa thể loại
     */
    public function destroy($id)
    {
        $genre = Genre::findOrFail($id);
        $genre->delete();

        return response([
            'success' => true,
            'message' => 'Xóa thể loại thành công'
        ]);
    }

    /**
     * Danh sách thể loại công khai (chỉ gồm id, name)
     */
    public function indexPublic()
    {
        $genres = Genre::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response([
            'success' => true,
            'data' => $genres
        ]);
    }
}
