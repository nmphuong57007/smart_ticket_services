<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\Ticket\TicketService;
use App\Http\Validator\Ticket\TicketPreviewValidator;
use App\Http\Resources\TicketPreviewResource;

class TicketController extends Controller
{
    protected TicketService $service;
    protected TicketPreviewValidator $validator;

    public function __construct(TicketService $service, TicketPreviewValidator $validator)
    {
        $this->service = $service;
        $this->validator = $validator;
    }

    /**
     * Preview ticket trước khi đặt (GET)
     */
    public function preview(Request $request)
    {
        // Lấy dữ liệu từ query string
        $input = $request->query();

        // Chuyển seat_ids và combo_ids từ string sang array
        if (isset($input['seat_ids']) && is_string($input['seat_ids'])) {
            $input['seat_ids'] = explode(',', $input['seat_ids']);
        }

        if (isset($input['combo_ids']) && is_string($input['combo_ids'])) {
            $input['combo_ids'] = explode(',', $input['combo_ids']);
        }

        // Lấy promotion_code (nếu có)
        $promotionCode = $input['promotion_code'] ?? null;

        // Validate dữ liệu
        $validation = $this->validator->validateWithStatus($input);

        if (!$validation['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validation['errors'],
            ], 422);
        }

        $validated = $validation['data'];

        // Gọi service preview ticket
        $ticketData = $this->service->previewTicket(
            $validated['showtime_id'],
            $validated['seat_ids'],
            $validated['combo_ids'] ?? [],
            $promotionCode
        );

        if (!$ticketData['success']) {
            return response()->json([
                'success' => false,
                'message' => $ticketData['message'],
                'data' => []
            ], 404);
        }

        // Dùng Resource để đảm bảo dữ liệu trả về đẹp và chuẩn
        return response()->json([
            'success' => true,
            'message' => 'Thông tin vé trước khi đặt',
            'data' => new TicketPreviewResource($ticketData['data']),
        ]);
    }
}
