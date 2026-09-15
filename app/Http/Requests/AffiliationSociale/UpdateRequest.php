<?php

namespace App\Http\Requests\AffiliationSociale;

use App\Enums\StatutAffiliation;
use Illuminate\Validation\Rule;

class UpdateRequest extends CreateRequest
{
    public function rules(): array
    {
        return [
            'organisme_id' => ['sometimes', 'integer', 'exists:organismes_sociaux,id'],
            'numero_affiliation' => ['sometimes', 'string', 'max:50'],
            'date_debut' => ['sometimes', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'statut' => ['sometimes', 'string', Rule::enum(StatutAffiliation::class)],
            'notes' => ['nullable', 'string'],
        ];
    }
}
