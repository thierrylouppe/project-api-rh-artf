<?php

namespace App\Http\Requests\Affectation;

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
            'agent_id'                   => ['required', 'integer', 'exists:agents,id'],
            'structurable_type'          => ['required', 'string', 'in:App\\Models\\Direction,App\\Models\\Service,App\\Models\\Bureau'],
            'structurable_id'            => ['required', 'integer'],
            'motif'                      => ['nullable', 'string'],
            'note_service'               => ['nullable', 'string'],
            'superieur_hierarchique_id'  => ['nullable', 'integer', 'exists:agents,id'],
            'date_affectation'           => ['required', 'date'],
        ];
    }
}
