<?php

namespace App\Http\Requests\Direction;

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
            'nom' => ['required', 'string', 'max:255'],
            'sigle' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'administration_id' => ['required', 'integer', 'exists:administrations,id'],
        ];
    }
}
