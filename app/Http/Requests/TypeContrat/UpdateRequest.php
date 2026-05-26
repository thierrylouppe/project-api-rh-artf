<?php

namespace App\Http\Requests\TypeContrat;

use Illuminate\Validation\Rule;

class UpdateRequest extends CreateRequest
{
    public function rules(): array
    {
        return [
            'nom'         => ['sometimes', 'string', 'max:255', Rule::unique('type_contrats', 'nom')->ignore($this->route('types_contrat'))],
            'sigle'       => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ];
    }
}
