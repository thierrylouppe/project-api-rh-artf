<?php

namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom'               => ['required', 'string', 'max:255'],
            'prenom'            => ['required', 'string', 'max:255'],
            'date_naissance'    => ['required', 'date', 'before:today'],
            'lieu_naissance'    => ['nullable', 'string', 'max:255'],
            'nationalite'       => ['nullable', 'string', 'max:100'],
            'genre'             => ['required', 'in:M,F'],
            'telephone'         => ['nullable', 'string', 'max:20'],
            'email_personnel'   => ['nullable', 'email', 'max:255'],
            'numero_cnss'       => ['nullable', 'string', 'max:50'],
            'rib_bancaire'      => ['nullable', 'string', 'max:100'],
            'diplome_id'        => ['nullable', 'integer', 'exists:diplomes,id'],
            'grade_id'          => ['nullable', 'integer', 'exists:grades,id'],
            'categorie_id'      => ['nullable', 'integer', 'exists:categories,id'],
            'echelon_id'        => ['nullable', 'integer', 'exists:echelons,id'],
            'fonction_id'       => ['nullable', 'integer', 'exists:fonctions,id'],
            'type_integration_id' => ['required', 'integer', 'exists:type_integrations,id'],
        ];
    }
}
