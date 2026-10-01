<?php

namespace App\Http\Requests\Avertissement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agent_id' => ['sometimes', 'integer', 'exists:agents,id'],
            'motif' => ['sometimes', 'string', 'min:3'],
            'date' => ['sometimes', 'date'],
        ];
    }
}
