<?php

namespace App\Http\Requests\Evaluation;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Mise à jour des données contextuelles d'une fiche
 * (jours d'absence, sanctions, avis).
 */
class UpdateContexteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jours_absence_non_justifiee' => ['sometimes', 'integer', 'min:0', 'max:365'],
            'sanctions'                   => ['sometimes', 'nullable', 'string', 'max:1000'],
            'avis_superieur'              => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
