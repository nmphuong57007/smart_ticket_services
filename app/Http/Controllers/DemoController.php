<?php

namespace App\Http\Controllers;

class DemoController extends Controller
{
    public function index()
    {
        // return $this->successResponse(['message' => 'Demo endpoint is working!'], 'Demo fetched successfully');
        // return $this->errorResponse('This is a demo error message', 500, ['detail' => 'Additional error details']);
        // return $this->validationErrorResponse(['field' => 'This field is required'], 'Validation failed for the demo request');
        // return $this->unauthorizedResponse('You are not authorized to access this demo endpoint');
        //  return $this->forbiddenResponse('Access to this demo endpoint is forbidden');
        return $this->paginatedResponse([
            'data' => [
                ['id' => 1, 'name' => 'Item 1'],
                ['id' => 2, 'name' => 'Item 2'],
            ],
            'current_page' => 1,
            'per_page' => 2,
            'total' => 10,
            'last_page' => 5,
        ], 'Demo paginated data fetched successfully');
    }
}