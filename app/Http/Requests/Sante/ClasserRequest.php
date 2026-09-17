<?php

namespace App\Http\Requests\Sante;

use Illuminate\Foundation\Http\FormRequest;

class ClasserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'commentaire' => ['nullable', 'string'],
        ];
    }
}
