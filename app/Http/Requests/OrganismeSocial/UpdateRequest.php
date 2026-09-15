<?php

namespace App\Http\Requests\OrganismeSocial;

use App\Enums\TypeOrganismeSocial;
use Illuminate\Validation\Rule;

class UpdateRequest extends CreateRequest
{
    public function rules(): array
    {
        return [
            'nom' => ['sometimes', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('organismes_sociaux', 'code')->ignore($this->route('id'))],
            'type' => ['sometimes', 'string', Rule::enum(TypeOrganismeSocial::class)],
            'description' => ['nullable', 'string'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'actif' => ['nullable', 'boolean'],
        ];
    }
}
