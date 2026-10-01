<?php

namespace App\Http\Requests\PaieAffectation;

class UpdateRequest extends CreateRequest
{
    public function rules(): array
    {
        return [
            'montant' => ['nullable', 'numeric', 'min:0'],
            'taux' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'quantite' => ['nullable', 'numeric', 'min:0'],
            'date_debut' => ['sometimes', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'motif' => ['nullable', 'string'],
            'prolongation_dg' => ['nullable', 'boolean'],
            'zone' => ['nullable', 'string', 'in:afrique,autre'],
            'cause' => ['nullable', 'string', 'in:maladie,accident_travail'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
