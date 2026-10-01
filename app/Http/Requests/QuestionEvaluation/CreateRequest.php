<?php

namespace App\Http\Requests\QuestionEvaluation;

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
            'libelle'      => ['required', 'string', 'max:255'],
            'type_critere' => ['required', 'in:competence_pro,assiduite,relation_sociale'],
            'bareme_max'   => ['required', 'numeric', 'min:0.5', 'max:20'],
            'ordre'        => ['nullable', 'integer', 'min:0'],
            'actif'        => ['nullable', 'boolean'],
        ];
    }
}
