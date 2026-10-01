<?php

namespace App\Http\Requests\InscriptionFormation;

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
            'agent_id' => ['required', 'integer', 'exists:agents,id'],
            'formation_id' => ['required', 'integer', 'exists:catalogue_formations,id'],
            'plan_id' => ['nullable', 'integer', 'exists:plans_formation,id'],
            'date_inscription' => ['nullable', 'date'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'admission_sur_titre' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
