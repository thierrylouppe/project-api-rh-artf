<?php

namespace App\Http\Requests\MotifAdministratif;

use Illuminate\Validation\Rule;

class UpdateRequest extends CreateRequest
{
    public function rules(): array
    {
        return [
            'nom'         => ['sometimes', 'string', 'max:255', Rule::unique('motifs_administratifs', 'nom')->ignore($this->route('motifs_administratif'))],
            'description' => ['nullable', 'string'],
        ];
    }
}
