<?php

namespace App\Http\Requests\TypeDocument;

use Illuminate\Validation\Rule;

class UpdateRequest extends CreateRequest
{
    public function rules(): array
    {
        return [
            'nom'         => ['sometimes', 'string', 'max:255', Rule::unique('type_documents', 'nom')->ignore($this->route('types_document'))],
            'description' => ['nullable', 'string'],
            'obligatoire' => ['nullable', 'boolean'],
        ];
    }
}
