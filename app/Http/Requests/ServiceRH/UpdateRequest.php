<?php

namespace App\Http\Requests\ServiceRH;

class UpdateRequest extends CreateRequest
{
    public function rules(): array
    {
        return [
            'nom' => ['sometimes', 'string', 'max:255'],
            'sigle' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'direction_id' => ['sometimes', 'integer', 'exists:directions,id'],
        ];
    }
}
