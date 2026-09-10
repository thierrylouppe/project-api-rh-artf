<?php

namespace App\Http\Requests\Evaluation;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Le notateur donne son avis (10–2000 chars) et signe la fiche en une action.
 * CCN ARTF art. 63.
 */
class AvisEtSignerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'avis_superieur' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'avis_superieur.required' => 'L\'avis du notateur est obligatoire avant de signer.',
            'avis_superieur.min'      => 'L\'avis doit comporter au moins :min caractères.',
            'avis_superieur.max'      => 'L\'avis ne peut pas dépasser :max caractères.',
        ];
    }
}
