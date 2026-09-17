<?php

namespace App\Http\Requests\Prestation;

use Illuminate\Foundation\Http\FormRequest;

class AccorderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_decision' => ['nullable', 'date'],
            'commentaire' => ['nullable', 'string'],
            'paie_annee' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'paie_mois' => ['nullable', 'integer', 'min:1', 'max:12'],
        ];
    }
}
