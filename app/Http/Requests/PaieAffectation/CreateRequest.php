<?php

namespace App\Http\Requests\PaieAffectation;

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
            'agent_id' => ['required', 'integer', 'exists:agents,id'],
            'paie_element_id' => ['required', 'integer', 'exists:paie_elements,id'],
            'montant' => ['nullable', 'numeric', 'min:0'],
            'taux' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'quantite' => ['nullable', 'numeric', 'min:0'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'motif' => ['nullable', 'string'],
            'prolongation_dg' => ['nullable', 'boolean'],
            'zone' => ['nullable', 'string', 'in:afrique,autre'],
            'cause' => ['nullable', 'string', 'in:maladie,accident_travail'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
