<?php

namespace App\Http\Requests\Avertissement;

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
            'agent_id' => ['required', 'integer', 'exists:agents,id'],
            'motif' => ['required', 'string', 'min:3'],
            'date' => ['required', 'date'],
        ];
    }
}
