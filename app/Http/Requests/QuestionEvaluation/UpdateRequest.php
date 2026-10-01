<?php

namespace App\Http\Requests\QuestionEvaluation;

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
            'libelle'      => ['sometimes', 'string', 'max:255'],
            'type_critere' => ['sometimes', 'in:competence_pro,assiduite,relation_sociale'],
            'bareme_max'   => ['sometimes', 'numeric', 'min:0.5', 'max:20'],
            'ordre'        => ['sometimes', 'nullable', 'integer', 'min:0'],
            'actif'        => ['sometimes', 'boolean'],
        ];
    }
}
