<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SeatUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && in_array($this->user()->role, ['admin']);
    }

    public function rules(): array
    {
        return [
            'room_id'   => 'sometimes|exists:rooms,id',
            'seat_code' => 'sometimes|string|max:10|unique:seats,seat_code,' . $this->id,
            'type'      => 'sometimes|in:normal,vip',
            'status'    => 'sometimes|in:available,booked',
            'price'     => 'sometimes|numeric|min:0',
        ];
    }
}
