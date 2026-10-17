<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'equipe_a' => ['required', 'string', 'max:150'],
            'equipe_b' => ['required', 'string', 'max:150', 'different:equipe_a'],
            'date_match' => ['required', 'date'],
            'competition' => ['required', 'string', 'max:150'],
        ];
    }
}