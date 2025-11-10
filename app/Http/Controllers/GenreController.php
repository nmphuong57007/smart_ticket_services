<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Http\Request;

class GenreController extends Controller
{
    public function index()
    {
        return response([
            'success' => true,
            'data' => Genre::orderBy('name')->get()
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:genres,name',
        ]);

        $genre = Genre::create($data);

        return response([
            'success' => true,
            'message' => 'Thêm thể loại thành công',
            'data' => $genre
        ], 201);
    }

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
            'data' => $genre
        ]);
    }

    public function destroy($id)
    {
        $genre = Genre::findOrFail($id);
        $genre->delete();

        return response(['success' => true, 'message' => 'Xóa thể loại thành công']);
    }

    public function indexPublic()
    {
        return response([
            'success' => true,
            'data' => Genre::where('is_active', true)->orderBy('name')->get(['id', 'name'])
        ]);
    }
}
