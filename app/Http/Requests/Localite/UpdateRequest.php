<?php

namespace App\Http\Requests\Localite;

use Illuminate\Validation\Rule;

class UpdateRequest extends CreateRequest
{
    public function rules(): array
    {
        return [
            'nom' => ['sometimes', 'string', 'max:255', Rule::unique('localites', 'nom')->ignore($this->route('localite'))],
            'sigle' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ];
    }
}
