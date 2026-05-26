<?php

namespace App\Http\Requests\Administration;

class UpdateRequest extends CreateRequest
{
    public function rules(): array
    {
        return [
            'nom' => ['sometimes', 'string', 'max:255'],
            'sigle' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'localite_id' => ['sometimes', 'integer', 'exists:localites,id'],
        ];
    }
}
