<?php

namespace App\Http\Requests\TypeSanction;

use App\Enums\CodeTypeSanction;
use App\Enums\GraviteSanction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255', 'unique:type_sanctions,nom'],
            'code' => ['nullable', 'string', Rule::enum(CodeTypeSanction::class), 'unique:type_sanctions,code'],
            'gravite' => ['required', 'string', Rule::enum(GraviteSanction::class)],
            'exige_nb_jours' => ['nullable', 'boolean'],
            'nb_jours_min' => ['nullable', 'integer', 'min:1', 'max:8'],
            'nb_jours_max' => ['nullable', 'integer', 'min:1', 'max:8', 'gte:nb_jours_min'],
            'description' => ['nullable', 'string'],
            'actif' => ['nullable', 'boolean'],
        ];
    }
}
