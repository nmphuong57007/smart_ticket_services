<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Showtime;
use Illuminate\Http\Request;

class ShowtimeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Lọc theo phim, phòng, hoặc ngày (nếu có)
        $query = Showtime::query()
            ->with(['movie', 'room'])
            ->orderBy('show_date', 'asc')
            ->orderBy('show_time', 'asc');

        if ($request->has('movie_id')) {
            $query->where('movie_id', $request->movie_id);
        }

        if ($request->has('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        if ($request->has('date')) {
            $query->whereDate('show_date', $request->date);
        }

        $showtimes = $query->get();

        // Nếu muốn trả thêm thông tin chi tiết phim và phòng
        $data = $showtimes->map(function ($item) {
            return [
                'id' => $item->id,
                'movie' => $item->movie->title ?? null,
                'room' => $item->room->name ?? null,
                'show_date' => $item->show_date,
                'show_time' => $item->show_time,
                'price' => $item->price,
                'format' => $item->format,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'movie_id' => 'required|exists:movies,id',
            'room_id' => 'required|exists:rooms,id',
            'show_date' => 'required|date',
            'show_time' => 'required',
            'price' => 'required|numeric|min:0',
            'format' => 'nullable|string|max:50'
        ]);

        // Kiểm tra trùng lịch trong cùng phòng
        $exists = Showtime::where('room_id', $request->room_id)
            ->where('show_date', $request->show_date)
            ->where('show_time', $request->show_time)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Đã có lịch chiếu trùng trong phòng này.'
            ], 409);
        }

        $showtime = Showtime::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $showtime
        ]);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $showtime = Showtime::findOrFail($id);

        $request->validate([
            'movie_id' => 'required|exists:movies,id',
            'room_id' => 'required|exists:rooms,id',
            'show_date' => 'required|date',
            'show_time' => 'required',
            'price' => 'required|numeric|min:0',
            'format' => 'nullable|string|max:50'
        ]);

        // Kiểm tra trùng giờ nếu đổi phòng hoặc thời gian
        $exists = Showtime::where('room_id', $request->room_id)
            ->where('show_date', $request->show_date)
            ->where('show_time', $request->show_time)
            ->where('id', '<>', $id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Giờ chiếu này đã tồn tại trong phòng.'
            ], 409);
        }

        $showtime->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật lịch chiếu thành công.',
            'data' => $showtime
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $showtime = Showtime::findOrFail($id);

        // Nếu đã có vé bán -> không cho xóa
        if ($showtime->tickets()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa, lịch chiếu đã có vé được bán.'
            ], 400);
        }

        $showtime->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa lịch chiếu thành công.'
        ]);
    }
}
