<?php

namespace App\Http\Requests\TypeIntegration;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nom'         => ['required', 'string', 'max:255', 'unique:type_integrations,nom'],
            'description' => ['nullable', 'string'],
        ];
    }
}
