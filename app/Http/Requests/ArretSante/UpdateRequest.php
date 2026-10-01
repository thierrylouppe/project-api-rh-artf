<?php

namespace App\Http\Requests\ArretSante;

use App\Enums\NatureArretSante;
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
            'nature' => ['sometimes', 'string', Rule::enum(NatureArretSante::class)],
            'date_fait' => ['sometimes', 'date'],
            'date_notification' => ['nullable', 'date'],
            'date_debut' => ['sometimes', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'structure_sanitaire_id' => ['sometimes', 'integer', 'exists:structures_sanitaires,id'],
            'demande_conge_id' => ['nullable', 'integer', 'exists:demande_conges,id'],
        ];
    }
}
