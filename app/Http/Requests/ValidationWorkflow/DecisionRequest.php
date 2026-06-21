<?php

namespace App\Http\Requests\ValidationWorkflow;

use Illuminate\Foundation\Http\FormRequest;

class DecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'commentaire' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
