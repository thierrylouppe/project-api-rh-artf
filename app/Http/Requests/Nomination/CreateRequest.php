<?php

namespace App\Http\Requests\Nomination;

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
            'agent_id'          => ['required', 'integer', 'exists:agents,id'],
            'poste'             => ['required', 'string', 'in:Directeur Général,Directeur Central,Directeur Départemental,Chef de Service,Chef de Bureau'],
            'structurable_type' => ['required', 'string', 'in:App\\Models\\Direction,App\\Models\\Service,App\\Models\\Bureau'],
            'structurable_id'   => ['required', 'integer'],
            'date_debut'        => ['required', 'date'],
            'type_acte'         => ['nullable', 'in:arrete,decision,note_service'],
        ];
    }
}
