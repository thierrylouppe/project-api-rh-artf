<?php

namespace App\Http\Requests\DossierIntegration;

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
            'poste_demande'  => ['nullable', 'string', 'max:255'],
            'nombre_postes'  => ['nullable', 'integer', 'min:1'],
            'motif'          => ['nullable', 'string'],
            'notes'          => ['nullable', 'string'],
        ];
    }
}
