<?php

namespace App\Http\Requests\DossierIntegration;

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
            'type_integration_id'  => ['required', 'integer', 'exists:type_integrations,id'],
            'demandeur_id'         => ['nullable', 'integer', 'exists:users,id'],
            'structurable_type'    => ['nullable', 'string', 'in:App\\Models\\Direction,App\\Models\\Service,App\\Models\\Bureau'],
            'structurable_id'      => ['nullable', 'integer'],
            'poste_demande'        => ['nullable', 'string', 'max:255'],
            'nombre_postes'        => ['nullable', 'integer', 'min:1'],
            'date_demande'         => ['nullable', 'date'],
            'motif'                => ['nullable', 'string'],
            'notes'                => ['nullable', 'string'],
        ];
    }
}
