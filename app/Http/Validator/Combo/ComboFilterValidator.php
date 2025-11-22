<?php

namespace App\Http\Validator\Combo;

use App\Http\Validator\BaseValidator;
use Illuminate\Support\Facades\Validator;

class ComboFilterValidator extends BaseValidator
{
    /**
     * Validate filters for combo list (index)
     */
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

    /**
     * Validate data when creating a combo
     */
    public function validateCreate(array $data): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ];

        $validator = Validator::make($data, $rules);

        return $validator->fails()
            ? ['success' => false, 'errors' => $validator->errors()]
            : ['success' => true];
    }

    /**
     * Validate data when updating a combo
     */
    public function validateUpdate(array $data): array
    {
        // Có thể dùng lại rule của create
        return $this->validateCreate($data);
    }
}
