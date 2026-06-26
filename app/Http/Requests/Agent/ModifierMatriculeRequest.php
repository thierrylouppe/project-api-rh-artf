<?php

namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;

class ModifierMatriculeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'matricule' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9\-]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'matricule.required' => 'Le matricule est obligatoire.',
            'matricule.regex'    => 'Le matricule ne peut contenir que des lettres majuscules, chiffres et tirets.',
        ];
    }
}
