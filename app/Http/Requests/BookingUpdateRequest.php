<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        return [
            'booking_status' => 'in:pending,confirmed,canceled,expired,refunded',
            'payment_status' => 'in:pending,paid,failed,refunded',
            'payment_method' => 'nullable|string|max:50',
        ];
    }
}
