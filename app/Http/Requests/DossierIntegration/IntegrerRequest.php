<?php

namespace App\Http\Requests\DossierIntegration;

use Illuminate\Foundation\Http\FormRequest;

class IntegrerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'numero_cnss' => ['nullable', 'string', 'max:50'],
        ];
    }
}
