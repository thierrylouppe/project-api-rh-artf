<?php

namespace App\Http\Requests\PalierAncienneteConge;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'anciennete_min' => ['sometimes', 'integer', 'min:0'],
            'anciennete_max' => ['nullable', 'integer', 'min:0'],
            'jours_bonus'    => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
