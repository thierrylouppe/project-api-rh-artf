<?php

namespace App\Http\Requests\ArretSante;

use App\Enums\NatureArretSante;
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
            'nature' => ['required', 'string', Rule::enum(NatureArretSante::class)],
            'date_fait' => ['required', 'date'],
            'date_notification' => ['nullable', 'date'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'structure_sanitaire_id' => ['required', 'integer', 'exists:structures_sanitaires,id'],
            'demande_conge_id' => ['nullable', 'integer', 'exists:demande_conges,id'],
        ];
    }
}
