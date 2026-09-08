<?php

namespace App\Http\Requests\PalierAncienneteConge;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'anciennete_min' => ['required', 'integer', 'min:0'],
            'anciennete_max' => ['nullable', 'integer', 'min:0'],
            'jours_bonus'    => ['required', 'integer', 'min:0'],
        ];
    }
}
