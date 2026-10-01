<?php

namespace App\Http\Requests\SalaireAgent;

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
            'agent_id'   => ['required', 'integer', 'exists:agents,id'],
            'contrat_id' => ['nullable', 'integer', 'exists:contrats,id'],
            'date_debut' => ['nullable', 'date'],
            'motif'      => ['nullable', 'string', 'max:500'],
        ];
    }
}
