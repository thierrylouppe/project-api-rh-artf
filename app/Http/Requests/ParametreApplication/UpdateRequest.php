<?php

namespace App\Http\Requests\ParametreApplication;

use Illuminate\Validation\Rule;

class UpdateRequest extends CreateRequest
{
    public function rules(): array
    {
        return [
            'cle' => ['sometimes', 'string', 'max:255', Rule::unique('parametres_application', 'cle')->ignore($this->route('parametres_application'))],
            'valeur' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ];
    }
}
