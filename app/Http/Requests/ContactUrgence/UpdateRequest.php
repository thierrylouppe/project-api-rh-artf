<?php

namespace App\Http\Requests\ContactUrgence;

class UpdateRequest extends CreateRequest
{
    public function rules(): array
    {
        return [
            'nom'       => ['sometimes', 'string', 'max:255'],
            'prenom'    => ['sometimes', 'string', 'max:255'],
            'telephone' => ['sometimes', 'string', 'max:20'],
            'relation'  => ['nullable', 'string', 'max:100'],
        ];
    }
}
