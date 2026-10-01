<?php

namespace App\Http\Requests\Sanction;

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
            'type_sanction_id' => ['required', 'integer', 'exists:type_sanctions,id'],
            'motif' => ['required', 'string', 'min:3'],
            'date_faits' => ['required', 'date'],
            'nb_jours' => ['nullable', 'integer', 'min:1', 'max:8'],
            'avec_indemnite' => ['nullable', 'boolean'],
        ];
    }
}
