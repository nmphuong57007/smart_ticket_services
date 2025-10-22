<?php

namespace App\Http\Validator\Combo;

use App\Http\Validator\BaseValidator;

class ComboFilterValidator extends BaseValidator
{
    public function rules(): array
    {
        return [
            'q' => 'nullable|string|max:255',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0|gte:min_price',
            'in_stock' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort_by' => 'nullable|in:price,name,stock',
            'sort_order' => 'nullable|in:asc,desc',
        ];
    }
}
