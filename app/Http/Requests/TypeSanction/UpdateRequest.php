<?php

namespace App\Http\Requests\TypeSanction;

use App\Enums\CodeTypeSanction;
use App\Enums\GraviteSanction;
use Illuminate\Validation\Rule;

class UpdateRequest extends CreateRequest
{
    public function rules(): array
    {
        return [
            'nom' => ['sometimes', 'string', 'max:255', Rule::unique('type_sanctions', 'nom')->ignore($this->route('id'))],
            'code' => ['nullable', 'string', Rule::enum(CodeTypeSanction::class), Rule::unique('type_sanctions', 'code')->ignore($this->route('id'))],
            'gravite' => ['sometimes', 'string', Rule::enum(GraviteSanction::class)],
            'exige_nb_jours' => ['nullable', 'boolean'],
            'nb_jours_min' => ['nullable', 'integer', 'min:1', 'max:8'],
            'nb_jours_max' => ['nullable', 'integer', 'min:1', 'max:8', 'gte:nb_jours_min'],
            'description' => ['nullable', 'string'],
            'actif' => ['nullable', 'boolean'],
        ];
    }
}
