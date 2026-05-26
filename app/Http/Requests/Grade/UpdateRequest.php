<?php

namespace App\Http\Requests\Grade;

use Illuminate\Validation\Rule;

class UpdateRequest extends CreateRequest
{
    public function rules(): array
    {
        return [
            'nom'         => ['sometimes', 'string', 'max:255', Rule::unique('grades', 'nom')->ignore($this->route('grade'))],
            'sigle'       => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'niveau'      => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }
}
