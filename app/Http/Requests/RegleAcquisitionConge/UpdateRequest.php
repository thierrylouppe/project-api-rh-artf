<?php

namespace App\Http\Requests\RegleAcquisitionConge;

class UpdateRequest extends CreateRequest
{
    public function rules(): array
    {
        return [
            'type_conge_id'  => ['sometimes', 'integer', 'exists:type_conges,id'],
            'jours_par_mois' => ['sometimes', 'numeric', 'min:0'],
            'jours_max'      => ['nullable', 'integer', 'min:0'],
        ];
    }
}
