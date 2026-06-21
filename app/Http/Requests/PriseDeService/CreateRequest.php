<?php

namespace App\Http\Requests\PriseDeService;

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
            'agent_id'                   => ['required', 'integer', 'exists:agents,id'],
            'dossier_integration_id'     => ['nullable', 'integer', 'exists:dossiers_integration,id'],
            'responsable_id'             => ['required', 'integer', 'exists:agents,id'],
            'date_prise_service'         => ['required', 'date'],
            'confirmation_presence'      => ['nullable', 'boolean'],
            'confirmation_installation'  => ['nullable', 'boolean'],
            'confirmation_equipements'   => ['nullable', 'boolean'],
            'observations'               => ['nullable', 'string'],
        ];
    }
}
