<?php

namespace App\Http\Requests\TypeConge;

use Illuminate\Validation\Rule;

class UpdateRequest extends CreateRequest
{
    public function rules(): array
    {
        return [
            'nom'                  => ['sometimes', 'string', 'max:255', Rule::unique('type_conges', 'nom')->ignore($this->route('types_conge'))],
            'description'          => ['nullable', 'string'],
            'jours_max'            => ['nullable', 'integer', 'min:0'],
            'necessite_n1'         => ['sometimes', 'boolean'],
            'necessite_rh'         => ['sometimes', 'boolean'],
            'necessite_dg'         => ['sometimes', 'boolean'],
            'debite_solde'         => ['sometimes', 'boolean'],
            'justificatif_requis'  => ['sometimes', 'boolean'],
        ];
    }
}
