<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOddRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'bookmaker' => ['required', 'string', 'max:100'],
            'cote_1' => ['required', 'numeric', 'gt:1', 'max:99999'],
            'cote_X' => ['required', 'numeric', 'gt:1', 'max:99999'],
            'cote_2' => ['required', 'numeric', 'gt:1', 'max:99999'],
            'probabilites_estimees' => ['nullable', 'array'],
            'probabilites_estimees.1' => ['required_with:probabilites_estimees', 'numeric', 'min:0', 'max:100'],
            'probabilites_estimees.X' => ['required_with:probabilites_estimees', 'numeric', 'min:0', 'max:100'],
            'probabilites_estimees.2' => ['required_with:probabilites_estimees', 'numeric', 'min:0', 'max:100'],
            'raison' => ['nullable', 'string', 'max:2000'],
        ];
    }
}