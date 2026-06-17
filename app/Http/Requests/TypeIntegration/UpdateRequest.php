<?php

namespace App\Http\Requests\TypeIntegration;

use Illuminate\Validation\Rule;

class UpdateRequest extends CreateRequest
{
    public function rules(): array
    {
        return [
            'nom'         => ['sometimes', 'string', 'max:255', Rule::unique('type_integrations', 'nom')->ignore($this->route('types_integration'))],
            'description' => ['nullable', 'string'],
        ];
    }
}
