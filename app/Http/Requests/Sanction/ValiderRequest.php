<?php

namespace App\Http\Requests\Sanction;

use Illuminate\Foundation\Http\FormRequest;

class ValiderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'string', 'min:3'],
            'date_decision' => ['nullable', 'date'],
            'commentaire' => ['nullable', 'string'],
            'nb_jours' => ['nullable', 'integer', 'min:1', 'max:8'],
            'date_debut_effet' => ['nullable', 'date'],
            'avec_indemnite' => ['nullable', 'boolean'],
        ];
    }
}
