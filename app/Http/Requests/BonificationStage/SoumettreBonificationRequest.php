<?php

namespace App\Http\Requests\BonificationStage;

use Illuminate\Foundation\Http\FormRequest;

class SoumettreBonificationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'agent_id'           => ['required', 'integer', 'exists:agents,id'],
            'date_debut_stage'   => ['required', 'date'],
            'date_fin_stage'     => ['required', 'date', 'after:date_debut_stage'],
            'type_document'      => ['required', 'in:certificat,attestation'],
            'reference_document' => ['nullable', 'string', 'max:255'],
        ];
    }
}
