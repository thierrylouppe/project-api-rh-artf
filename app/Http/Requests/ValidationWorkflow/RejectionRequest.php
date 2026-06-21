<?php

namespace App\Http\Requests\ValidationWorkflow;

use Illuminate\Foundation\Http\FormRequest;

class RejectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'commentaire' => ['required', 'string', 'max:1000'],
        ];
    }
}
