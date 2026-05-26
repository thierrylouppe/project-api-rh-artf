<?php

namespace App\Http\Requests\Bureau;

class UpdateRequest extends CreateRequest
{
    public function rules(): array
    {
        return [
            'nom' => ['sometimes', 'string', 'max:255'],
            'sigle' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'service_id' => ['sometimes', 'integer', 'exists:services,id'],
        ];
    }
}
