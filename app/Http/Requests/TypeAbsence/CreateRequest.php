<?php

namespace App\Http\Requests\TypeAbsence;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nom'                   => ['required', 'string', 'max:255', 'unique:type_absences,nom'],
            'description'           => ['nullable', 'string'],
            'justification_requise' => ['nullable', 'boolean'],
        ];
    }
}
