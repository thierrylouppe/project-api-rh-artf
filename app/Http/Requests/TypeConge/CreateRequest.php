<?php

namespace App\Http\Requests\TypeConge;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nom'                  => ['required', 'string', 'max:255', 'unique:type_conges,nom'],
            'description'          => ['nullable', 'string'],
            'jours_max'            => ['nullable', 'integer', 'min:0'],
            'necessite_n1'         => ['sometimes', 'boolean'],
            'necessite_rh'         => ['sometimes', 'boolean'],
            'necessite_dg'         => ['sometimes', 'boolean'],
            'debite_solde'         => ['sometimes', 'boolean'],
            'justificatif_requis'  => ['sometimes', 'boolean'],
        ];
    }
}
