<?php

namespace App\Http\Requests\ContactUrgence;

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
            'nom'       => ['required', 'string', 'max:255'],
            'prenom'    => ['required', 'string', 'max:255'],
            'telephone' => ['required', 'string', 'max:20'],
            'relation'  => ['nullable', 'string', 'max:100'],
        ];
    }
}
