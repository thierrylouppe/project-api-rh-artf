<?php

namespace App\Http\Requests\JourFerie;

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
            'date'      => ['required', 'date'],
            'recurrent' => ['sometimes', 'boolean'],
        ];
    }
}
