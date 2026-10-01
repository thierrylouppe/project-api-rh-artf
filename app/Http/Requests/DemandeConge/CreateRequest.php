<?php

namespace App\Http\Requests\DemandeConge;

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
            'agent_id'      => ['required', 'integer', 'exists:agents,id'],
            'type_conge_id' => ['required', 'integer', 'exists:type_conges,id'],
            'date_debut'    => ['required', 'date'],
            'date_fin'      => ['required', 'date', 'after_or_equal:date_debut'],
            'motif'         => ['nullable', 'string'],
            'justificatif'  => ['nullable', 'file', 'max:10240'],
        ];
    }
}
