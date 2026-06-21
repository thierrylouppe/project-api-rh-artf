<?php

namespace App\Http\Requests\DossierIntegration;

use Illuminate\Foundation\Http\FormRequest;

class AssignerMatriculeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'matricule' => ['required', 'string', 'max:50', 'unique:agents,matricule'],
        ];
    }

    public function messages(): array
    {
        return [
            'matricule.unique' => 'Ce matricule est déjà attribué à un autre agent.',
        ];
    }
}
