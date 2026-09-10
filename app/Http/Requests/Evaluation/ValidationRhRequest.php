<?php

namespace App\Http\Requests\Evaluation;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation RH (conformité ou rejet) d'une fiche.
 */
class ValidationRhRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'conforme'     => ['required', 'boolean'],
            'commentaire'  => ['nullable', 'string', 'max:1000'],
        ];
    }
}
