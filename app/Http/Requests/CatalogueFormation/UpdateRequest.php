<?php

namespace App\Http\Requests\CatalogueFormation;

use App\Enums\ModaliteFormation;
use App\Enums\TypeActionFormation;
use Illuminate\Validation\Rule;

class UpdateRequest extends CreateRequest
{
    public function rules(): array
    {
        return [
            'titre' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'organisme' => ['nullable', 'string', 'max:255'],
            'lieu' => ['nullable', 'string', 'max:255'],
            'modalite' => ['sometimes', 'string', Rule::enum(ModaliteFormation::class)],
            'type_action' => ['sometimes', 'string', Rule::enum(TypeActionFormation::class)],
            'duree_jours' => ['sometimes', 'integer', 'min:1', 'max:2000'],
            'cout' => ['nullable', 'numeric', 'min:0'],
            'anciennete_min_ans' => ['nullable', 'integer', 'min:0', 'max:10'],
            'debit_formation_mois' => ['nullable', 'integer', 'min:1', 'max:60'],
            'actif' => ['nullable', 'boolean'],
        ];
    }
}
