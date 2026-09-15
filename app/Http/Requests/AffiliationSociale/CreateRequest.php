<?php

namespace App\Http\Requests\AffiliationSociale;

use App\Enums\StatutAffiliation;
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
            'organisme_id' => ['required', 'integer', 'exists:organismes_sociaux,id'],
            'numero_affiliation' => ['nullable', 'string', 'max:50'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'statut' => ['nullable', 'string', Rule::enum(StatutAffiliation::class)],
            'notes' => ['nullable', 'string'],
        ];
    }
}
