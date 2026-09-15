<?php

namespace App\Http\Requests\Sanction;

use Illuminate\Foundation\Http\FormRequest;

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
            'type_sanction_id' => ['sometimes', 'integer', 'exists:type_sanctions,id'],
            'motif' => ['sometimes', 'string', 'min:3'],
            'date_faits' => ['sometimes', 'date'],
            'nb_jours' => ['nullable', 'integer', 'min:1', 'max:8'],
            'avec_indemnite' => ['nullable', 'boolean'],
            'notes_instruction' => ['nullable', 'string'],
            'decision' => ['nullable', 'string'],
        ];
    }
}
