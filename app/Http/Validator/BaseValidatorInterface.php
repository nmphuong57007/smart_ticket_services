<?php

namespace App\Http\Validator;

interface BaseValidatorInterface
{
    /**
     * Validate the given data against rules
     *
     * @param array $data
     * @return array
     */
    public function validate(array $data): array;

    /**
     * Get validation rules
     *
     * @return array
     */
    public function rules(): array;

    /**
     * Get custom error messages
     *
     * @return array
     */
    public function messages(): array;
}