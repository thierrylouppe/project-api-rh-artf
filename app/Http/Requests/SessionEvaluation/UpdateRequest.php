<?php

namespace App\Http\Requests\SessionEvaluation;

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
            'debut_session' => ['sometimes', 'date'],
            'fin_session'   => ['sometimes', 'nullable', 'date', 'after:debut_session'],
            'type_annee'    => ['sometimes', 'nullable', 'in:paire,impaire'],
            'semestre'      => ['sometimes', 'nullable', 'integer', 'in:1,2'],
            'description'   => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
