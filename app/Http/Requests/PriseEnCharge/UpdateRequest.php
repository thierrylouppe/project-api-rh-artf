<?php

namespace App\Http\Requests\PriseEnCharge;

use App\Enums\TypePriseEnCharge;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agent_id' => ['sometimes', 'integer', 'exists:agents,id'],
            'type' => ['sometimes', 'string', Rule::enum(TypePriseEnCharge::class)],
            'date_soins' => ['sometimes', 'date'],
            'structure_sanitaire_id' => ['sometimes', 'integer', 'exists:structures_sanitaires,id'],
            'ayant_droit_id' => ['nullable', 'integer', 'exists:ayants_droit,id'],
            'montant_facture' => ['nullable', 'integer', 'min:1'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'lieu' => ['nullable', 'string', 'max:255'],
            'at_mp' => ['nullable', 'boolean'],
        ];
    }
}
