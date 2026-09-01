<?php

namespace App\Http\Requests\JourFerie;

class UpdateRequest extends CreateRequest
{
    public function rules(): array
    {
        return [
            'nom'       => ['sometimes', 'string', 'max:255'],
            'date'      => ['sometimes', 'date'],
            'recurrent' => ['sometimes', 'boolean'],
        ];
    }
}
