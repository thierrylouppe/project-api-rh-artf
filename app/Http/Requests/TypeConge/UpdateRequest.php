<?php

namespace App\Http\Requests\TypeConge;

use Illuminate\Validation\Rule;

class UpdateRequest extends CreateRequest
{
    public function rules(): array
    {
        return [
            'nom'         => ['sometimes', 'string', 'max:255', Rule::unique('type_conges', 'nom')->ignore($this->route('types_conge'))],
            'description' => ['nullable', 'string'],
            'jours_max'   => ['nullable', 'integer', 'min:0'],
        ];
    }
}
