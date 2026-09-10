<?php

namespace App\Http\Requests\Evaluation;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Saisie d'une note sur un critère de la fiche (par le notateur).
 */
class NoterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question_id'  => ['required', 'integer', 'exists:question_evaluations,id'],
            'note_obtenue' => ['required', 'numeric', 'min:0'],
            'commentaire'  => ['nullable', 'string', 'max:500'],
        ];
    }
}
