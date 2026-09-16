<?php

namespace App\Http\Requests\PositionConventionnelle;

use App\Enums\TypePositionConventionnelle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agent_id'             => ['required', 'integer', 'exists:agents,id'],
            'type'                 => ['required', 'string', Rule::enum(TypePositionConventionnelle::class)],
            'date_debut'           => ['required', 'date'],
            'date_fin'             => ['required', 'date', 'after_or_equal:date_debut'],
            'organisme_accueil'    => ['nullable', 'string', 'max:255'],
            'consentement_agent'   => ['nullable', 'boolean'],
            'detachement_office'   => ['nullable', 'boolean'],
            'commentaire'          => ['nullable', 'string', 'max:2000'],
            'piece_path'           => ['nullable', 'string', 'max:500'],
        ];
    }
}
