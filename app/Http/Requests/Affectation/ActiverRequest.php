<?php

namespace App\Http\Requests\Affectation;

use Illuminate\Foundation\Http\FormRequest;

class ActiverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dossier_integration_id' => ['nullable', 'integer', 'exists:dossier_integrations,id'],
        ];
    }
}
