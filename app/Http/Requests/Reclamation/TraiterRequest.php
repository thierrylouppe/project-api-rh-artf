<?php

namespace App\Http\Requests\Reclamation;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Traitement RH d'une réclamation.
 */
class TraiterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'acceptee'    => ['required', 'boolean'],
            'commentaire' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'acceptee.required' => 'La décision (acceptée / rejetée) est obligatoire.',
        ];
    }
}
