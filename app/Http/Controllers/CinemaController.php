<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\Cinema\CinemaService;
use App\Http\Requests\CinemaStoreRequest;
use App\Http\Requests\CinemaUpdateRequest;
use App\Http\Resources\CinemaResource;
use App\Http\Validator\Cinema\CinemaFilterValidator;

class CinemaController extends Controller
{
    protected CinemaService $cinemaService;
    protected CinemaFilterValidator $cinemaFilterValidator;

    public function __construct(CinemaService $cinemaService, CinemaFilterValidator $cinemaFilterValidator)
    {
        $this->cinemaService = $cinemaService;
        $this->cinemaFilterValidator = $cinemaFilterValidator;
    }

    /**
     * Lấy danh sách rạp (phân trang + filter + sort)
     */
    public function index(Request $request)
    {
        try {
            //  Validate query params bằng CinemaFilterValidator
            $validationResult = $this->cinemaFilterValidator->validateWithStatus($request->query());
            if (!$validationResult['success']) {
                return response($validationResult, 422);
            }

            $filters = [
                'name'       => $request->query('name'),
                'address'    => $request->query('address'),
                'status'     => $request->query('status'),
                'sort_by'    => $request->query('sort_by', 'id'),
                'sort_order' => $request->query('sort_order', 'asc'),
                'per_page'   => $request->query('per_page', 10),
            ];

            $cinemas = $this->cinemaService->getCinemas($filters);

            return response([
                'success' => true,
                'message' => 'Lấy danh sách rạp chiếu thành công',
                'data' => [
                    'cinemas' => CinemaResource::collection($cinemas->items()),
                    'pagination' => [
                        'current_page' => $cinemas->currentPage(),
                        'last_page'    => $cinemas->lastPage(),
                        'total'        => $cinemas->total(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Lấy danh sách rạp thất bại',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy chi tiết rạp theo ID (kèm danh sách phòng)
     */
    public function show($id)
    {
        try {
            $cinema = $this->cinemaService->getCinemaById($id);

            if (!$cinema) {
                return response([
                    'success' => false,
                    'message' => 'Không tìm thấy rạp chiếu',
                ], 404);
            }

            return response([
                'success' => true,
                'message' => 'Lấy thông tin rạp chiếu thành công',
                'data' => new CinemaResource($cinema),
            ]);
        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Lấy thông tin rạp thất bại',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Thêm rạp mới
     */
    public function store(CinemaStoreRequest $request)
    {
        try {
            $data = $request->validated();
            $cinema = $this->cinemaService->createCinema($data);

            return response([
                'success' => true,
                'message' => 'Thêm rạp chiếu thành công',
                'data' => new CinemaResource($cinema),
            ], 201);
        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Thêm rạp thất bại',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     *  Cập nhật rạp
     */
    public function update(CinemaUpdateRequest $request, $id)
    {
        try {
            $cinema = $this->cinemaService->getCinemaById($id);
            if (!$cinema) {
                return response(['success' => false, 'message' => 'Không tìm thấy rạp'], 404);
            }

            $updated = $this->cinemaService->updateCinema($cinema, $request->validated());

            return response([
                'success' => true,
                'message' => 'Cập nhật rạp thành công',
                'data' => new CinemaResource($updated),
            ]);
        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Cập nhật rạp thất bại',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Xóa rạp
     */
    public function destroy($id)
    {
        try {
            $cinema = $this->cinemaService->getCinemaById($id);
            if (!$cinema) {
                return response(['success' => false, 'message' => 'Không tìm thấy rạp'], 404);
            }

            $this->cinemaService->deleteCinema($cinema);

            return response([
                'success' => true,
                'message' => 'Xóa rạp thành công',
            ]);
        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Xóa rạp thất bại',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Đổi trạng thái rạp (active/inactive)
     */
    public function changeStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:active,inactive']);

        try {
            $cinema = $this->cinemaService->getCinemaById($id);
            if (!$cinema) {
                return response(['success' => false, 'message' => 'Không tìm thấy rạp'], 404);
            }

            $updated = $this->cinemaService->updateCinema($cinema, [
                'status' => $request->input('status'),
            ]);

            return response([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công',
                'data' => new CinemaResource($updated),
            ]);
        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Cập nhật trạng thái thất bại',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy danh sách phòng của rạp
     */
    public function rooms($cinemaId)
    {
        try {
            $rooms = $this->cinemaService->getRoomsByCinema($cinemaId);

            return response([
                'success' => true,
                'message' => 'Lấy danh sách phòng của rạp thành công',
                'data' => $rooms,
            ]);
        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Lấy danh sách phòng thất bại',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy toàn bộ lịch chiếu của rạp
     */
    public function showtimes($cinemaId)
    {
        try {
            $showtimes = $this->cinemaService->getShowtimesByCinema($cinemaId);

            return response([
                'success' => true,
                'message' => 'Lấy lịch chiếu của rạp thành công',
                'data' => $showtimes,
            ]);
        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Lấy lịch chiếu thất bại',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     *  Thống kê tổng quan
     */
    public function statistics()
    {
        try {
            $stats = $this->cinemaService->getCinemaStatistics();

            return response([
                'success' => true,
                'message' => 'Lấy thống kê thành công',
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response([
                'success' => false,
                'message' => 'Lấy thống kê thất bại',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
