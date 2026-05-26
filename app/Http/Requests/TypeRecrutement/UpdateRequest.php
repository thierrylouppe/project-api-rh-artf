<?php

namespace App\Http\Requests\TypeRecrutement;

use Illuminate\Validation\Rule;

class UpdateRequest extends CreateRequest
{
    public function rules(): array
    {
        return [
            'nom'         => ['sometimes', 'string', 'max:255', Rule::unique('type_recrutements', 'nom')->ignore($this->route('types_recrutement'))],
            'description' => ['nullable', 'string'],
        ];
    }
}
