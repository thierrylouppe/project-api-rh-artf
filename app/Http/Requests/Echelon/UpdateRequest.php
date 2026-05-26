<?php

namespace App\Http\Requests\Echelon;

use Illuminate\Validation\Rule;

class UpdateRequest extends CreateRequest
{
    public function rules(): array
    {
        return [
            'nom'         => ['sometimes', 'string', 'max:255', Rule::unique('echelons', 'nom')->ignore($this->route('echelon'))],
            'numero'      => ['sometimes', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
        ];
    }
}
