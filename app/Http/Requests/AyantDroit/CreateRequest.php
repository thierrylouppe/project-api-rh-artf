<?php

namespace App\Http\Requests\AyantDroit;

use App\Enums\LienJuridiqueAyantDroit;
use App\Enums\QualiteAgeAyantDroit;
use App\Enums\TypeAyantDroit;
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
            'agent_id' => ['required', 'integer', 'exists:agents,id'],
            'type' => ['required', 'string', Rule::enum(TypeAyantDroit::class)],
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'date_naissance' => ['required', 'date', 'before_or_equal:today'],
            'sexe' => ['nullable', 'string', Rule::in(['M', 'F'])],
            'lien_juridique' => ['required', 'string', Rule::enum(LienJuridiqueAyantDroit::class)],
            'qualite_age' => ['nullable', 'string', Rule::enum(QualiteAgeAyantDroit::class)],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'actif' => ['nullable', 'boolean'],
        ];
    }
}
