<?php

namespace App\Http\Requests\SituationFamiliale;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'statut_matrimonial' => ['nullable', 'string', Rule::in(['celibataire', 'marie', 'divorce', 'veuf', 'union_libre'])],
            'nb_enfants'         => ['nullable', 'integer', 'min:0', 'max:30'],
        ];
    }
}
