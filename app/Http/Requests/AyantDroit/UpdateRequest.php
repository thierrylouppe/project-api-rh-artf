<?php

namespace App\Http\Requests\AyantDroit;

use App\Enums\LienJuridiqueAyantDroit;
use App\Enums\QualiteAgeAyantDroit;
use App\Enums\TypeAyantDroit;
use Illuminate\Validation\Rule;

class UpdateRequest extends CreateRequest
{
    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'string', Rule::enum(TypeAyantDroit::class)],
            'nom' => ['sometimes', 'string', 'max:255'],
            'prenom' => ['sometimes', 'string', 'max:255'],
            'date_naissance' => ['sometimes', 'date', 'before_or_equal:today'],
            'sexe' => ['nullable', 'string', Rule::in(['M', 'F'])],
            'lien_juridique' => ['sometimes', 'string', Rule::enum(LienJuridiqueAyantDroit::class)],
            'qualite_age' => ['nullable', 'string', Rule::enum(QualiteAgeAyantDroit::class)],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'actif' => ['nullable', 'boolean'],
        ];
    }
}
