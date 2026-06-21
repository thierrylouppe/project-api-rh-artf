<?php

namespace App\Http\Requests\DossierIntegration;

use Illuminate\Foundation\Http\FormRequest;

class TransitionRequest extends FormRequest
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
