<?php

namespace App\Http\Requests\Direction;

class UpdateRequest extends CreateRequest
{
    public function rules(): array
    {
        return [
            'nom' => ['sometimes', 'string', 'max:255'],
            'sigle' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'administration_id' => ['sometimes', 'integer', 'exists:administrations,id'],
        ];
    }
}
