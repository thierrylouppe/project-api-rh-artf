<?php

namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom'               => ['sometimes', 'string', 'max:255'],
            'prenom'            => ['sometimes', 'string', 'max:255'],
            'date_naissance'    => ['sometimes', 'date', 'before:today'],
            'lieu_naissance'    => ['nullable', 'string', 'max:255'],
            'nationalite'       => ['nullable', 'string', 'max:100'],
            'genre'             => ['sometimes', 'in:M,F'],
            'telephone'         => ['nullable', 'string', 'max:20'],
            'email_personnel'   => ['nullable', 'email', 'max:255'],
            'numero_cnss'       => ['nullable', 'string', 'max:50'],
            'rib_bancaire'      => ['nullable', 'string', 'max:100'],
            'grade_id'          => ['nullable', 'integer', 'exists:grades,id'],
            'categorie_id'      => ['nullable', 'integer', 'exists:categories,id'],
            'echelon_id'        => ['nullable', 'integer', 'exists:echelons,id'],
            'fonction_id'       => ['nullable', 'integer', 'exists:fonctions,id'],
            'statut'            => ['sometimes', 'in:actif,inactif,suspendu,retraite'],
        ];
    }
}
