<?php

namespace App\Http\Validator\Cinema;

use App\Http\Validator\BaseValidator;

class CinemaFilterValidator extends BaseValidator
{
    /**
     * Vì hệ thống chỉ còn 1 rạp duy nhất nên không cần filter.
     */
    public function rules(): array
    {
        return [];
    }

    public function messages(): array
    {
        return [];
    }
}
