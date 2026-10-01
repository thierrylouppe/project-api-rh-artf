<?php

namespace App\Http\Requests\Sanction;

use Illuminate\Foundation\Http\FormRequest;

class InstruireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes_instruction' => ['required', 'string', 'min:3'],
            'decision' => ['nullable', 'string'],
        ];
    }
}
