<?php

namespace App\Http\Requests\CertificationFormation;

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
            'formation_id' => ['required', 'integer', 'exists:catalogue_formations,id'],
            'inscription_id' => ['nullable', 'integer', 'exists:inscriptions_formation,id'],
            'diplome_id' => ['nullable', 'integer', 'exists:diplomes,id'],
            'date_obtention' => ['required', 'date', 'before_or_equal:today'],
            'reference' => ['nullable', 'string', 'max:100'],
            'fichier' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
        ];
    }
}
