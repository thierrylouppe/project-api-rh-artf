<?php

namespace App\Http\Requests\TypeDocument;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nom'         => ['required', 'string', 'max:255', 'unique:type_documents,nom'],
            'description' => ['nullable', 'string'],
            'obligatoire' => ['nullable', 'boolean'],
        ];
    }
}
