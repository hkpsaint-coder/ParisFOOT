<?php

namespace App\Http\Requests;

class UpdateMatchRequest extends StoreMatchRequest
{
    public function rules(): array
    {
        return array_map(
            fn (array $rules): array => array_values(array_filter($rules, fn (string $rule): bool => $rule !== 'required')),
            parent::rules(),
        );
    }
}