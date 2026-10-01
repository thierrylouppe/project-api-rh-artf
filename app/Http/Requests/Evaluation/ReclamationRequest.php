<?php

namespace App\Http\Requests\Evaluation;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Dépôt d'une réclamation par l'agent (CCN ARTF art. 65).
 */
class ReclamationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motif' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'motif.required' => 'Le motif de la réclamation est obligatoire.',
            'motif.min'      => 'Le motif doit comporter au moins :min caractères.',
        ];
    }
}
