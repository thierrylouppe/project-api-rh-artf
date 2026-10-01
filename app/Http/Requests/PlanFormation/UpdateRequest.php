<?php

namespace App\Http\Requests\PlanFormation;

class UpdateRequest extends CreateRequest
{
    public function rules(): array
    {
        return [
            'annee' => ['sometimes', 'integer', 'min:2020', 'max:2100'],
            'titre' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }
}
