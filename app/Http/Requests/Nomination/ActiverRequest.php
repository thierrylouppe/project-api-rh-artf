<?php

namespace App\Http\Requests\Nomination;

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
            // Conservé pour compatibilité FE — ignoré côté métier (carrière ≠ dossier).
            'dossier_integration_id' => ['nullable', 'integer', 'exists:dossiers_integration,id'],
        ];
    }
}
