<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
    /**
     * Chuẩn hóa response thành công
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function successResponse($data = null, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => now()->toISOString(),
            'data' => $data
        ];

        return response()->json($response, $statusCode);
    }

    /**
     * Chuẩn hóa response lỗi
     *
     * @param string $message
     * @param int $statusCode
     * @param array|null $errors
     * @return JsonResponse
     */
    protected function errorResponse(string $message = 'Error', int $statusCode = 400, array $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        $response['timestamp'] = now()->toISOString();

        return response()->json($response, $statusCode);
    }

    /**
     * Response cho dữ liệu phân trang
     *
     * @param mixed $data
     * @param string $message
     * @return JsonResponse
     */
    protected function paginatedResponse($data, string $message = 'Success'): JsonResponse
    {
        // Kiểm tra xem $data có phải là object và có phương thức items() không
        if (is_object($data) && method_exists($data, 'items')) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
                'timestamp' => now()->toISOString(),
                'data' => $data->items()
            ]);
        }

        // Xử lý trường hợp data là array có sẵn các key pagination
        if (is_array($data) && isset($data['data'])) {
            $response = [
                'success' => true,
                'message' => $message
            ];

            // Thêm các thông tin pagination nếu có
            $paginationKeys = ['current_page', 'last_page', 'per_page', 'total', 'from', 'to'];
            foreach ($paginationKeys as $key) {
                if (isset($data[$key])) {
                    $response[$key] = $data[$key];
                }
            }

            $response['timestamp'] = now()->toISOString();
            $response['data'] = $data['data']; // Data luôn ở cuối

            return response()->json($response);
        }

        // Nếu không phải paginated object, trả về như data bình thường
        return $this->successResponse($data, $message);
    }

    /**
     * Response cho validation errors
     *
     * @param array $errors
     * @param string $message
     * @return JsonResponse
     */
    protected function validationErrorResponse(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->errorResponse($message, 422, $errors);
    }

    /**
     * Response cho unauthorized
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * Response cho forbidden
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }

    /**
     * Response cho not found
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function notFoundResponse(string $message = 'Not found'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Response cho server error
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function serverErrorResponse(string $message = 'Internal server error'): JsonResponse
    {
        return $this->errorResponse($message, 500);
    }
}
