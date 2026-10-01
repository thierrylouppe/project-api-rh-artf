<?php

namespace App\Http\Requests\Prestation;

use App\Enums\TypePrestation;
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
            'type' => ['required', 'string', Rule::enum(TypePrestation::class)],
            'date_fait' => ['required', 'date'],
            'ayant_droit_id' => ['nullable', 'integer', 'exists:ayants_droit,id'],
            'beneficiaire_libelle' => ['nullable', 'string', 'max:255'],
            'transport_corps' => ['nullable', 'boolean'],
            'montant_demande' => ['nullable', 'integer', 'min:1', 'max:'.TypePrestation::PLAFOND_FUNERAIRES],
        ];
    }
}
