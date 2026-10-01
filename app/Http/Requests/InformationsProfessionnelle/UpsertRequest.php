<?php

namespace App\Http\Requests\InformationsProfessionnelle;

use Illuminate\Foundation\Http\FormRequest;

class UpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'diplome_id'         => ['nullable', 'integer', 'exists:diplomes,id'],
            'niveau_etude'       => ['nullable', 'string', 'max:100'],
            'specialite'         => ['nullable', 'string', 'max:255'],
            'annees_experience'  => ['nullable', 'integer', 'min:0', 'max:70'],
            'etablissement'      => ['nullable', 'string', 'max:255'],
        ];
    }
}
