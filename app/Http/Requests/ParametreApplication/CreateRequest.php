<?php

namespace App\Http\Requests\ParametreApplication;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cle' => ['required', 'string', 'max:255', 'unique:parametres_application,cle'],
            'valeur' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ];
    }
}
