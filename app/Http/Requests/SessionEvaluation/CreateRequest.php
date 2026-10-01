<?php

namespace App\Http\Requests\SessionEvaluation;

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
            'debut_session' => ['required', 'date'],
            'fin_session'   => ['nullable', 'date', 'after:debut_session'],
            'type_annee'    => ['nullable', 'in:paire,impaire'],
            'semestre'      => ['nullable', 'integer', 'in:1,2'],
            'description'   => ['nullable', 'string', 'max:500'],
        ];
    }
}
