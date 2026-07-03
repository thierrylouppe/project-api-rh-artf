<?php

namespace App\Http\Requests\Affectation;

use Illuminate\Foundation\Http\FormRequest;

class RejeterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'commentaire' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'commentaire.required' => 'Un motif de rejet est obligatoire.',
            'commentaire.min'      => 'Le motif doit comporter au moins 5 caractères.',
        ];
    }
}
