<?php

namespace App\Http\Requests\Contrat;

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
            'agent_id'               => ['required', 'integer', 'exists:agents,id'],
            'type_contrat_id'        => ['required', 'integer', 'exists:type_contrats,id'],
            'dossier_integration_id' => ['nullable', 'integer', 'exists:dossiers_integration,id'],
            'fonction_id'            => ['nullable', 'integer', 'exists:fonctions,id'],
            'date_debut'             => ['required', 'date'],
            'date_fin'               => ['nullable', 'date', 'after:date_debut'],
            'remuneration'           => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
