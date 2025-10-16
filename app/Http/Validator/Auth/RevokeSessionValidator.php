<?php

namespace App\Http\Validator\Auth;

use App\Http\Validator\BaseValidator;

class RevokeSessionValidator extends BaseValidator
{
    /**
     * Get validation rules
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'token_id' => 'required|integer'
        ];
    }

    /**
     * Get custom error messages
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'token_id.required' => 'ID phiên không được để trống',
            'token_id.integer' => 'ID phiên phải là số nguyên'
        ];
    }
}