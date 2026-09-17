<?php

namespace App\Http\Requests\Sante;

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
        ];
    }
}
