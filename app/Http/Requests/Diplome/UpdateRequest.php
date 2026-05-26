<?php

namespace App\Http\Requests\Diplome;

use Illuminate\Validation\Rule;

class UpdateRequest extends CreateRequest
{
    public function rules(): array
    {
        return [
            'nom'         => ['sometimes', 'string', 'max:255', Rule::unique('diplomes', 'nom')->ignore($this->route('diplome'))],
            'sigle'       => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ];
    }
}
