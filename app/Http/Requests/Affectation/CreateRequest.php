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
            'agent_id'                  => ['required', 'integer', 'exists:agents,id'],
            'structurable_type'         => ['required', 'string', 'in:App\\Models\\Direction,App\\Models\\Service,App\\Models\\Bureau'],
            'structurable_id'           => ['required', 'integer'],
            'motif'                     => ['nullable', 'string', 'max:1000'],
            'note_service'              => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'superieur_hierarchique_id' => ['nullable', 'integer', 'exists:agents,id'],
            'date_affectation'          => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'note_service.mimes' => 'La note de service doit être un fichier PDF, JPG ou PNG.',
            'note_service.max'   => 'La note de service ne doit pas dépasser 10 Mo.',
        ];
    }
}
