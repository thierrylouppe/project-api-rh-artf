<?php

namespace App\Http\Requests\TypeAbsence;

use Illuminate\Validation\Rule;

class UpdateRequest extends CreateRequest
{
    public function rules(): array
    {
        return [
            'nom'                   => ['sometimes', 'string', 'max:255', Rule::unique('type_absences', 'nom')->ignore($this->route('types_absence'))],
            'description'           => ['nullable', 'string'],
            'justification_requise' => ['nullable', 'boolean'],
        ];
    }
}
