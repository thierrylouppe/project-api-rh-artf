<?php

namespace App\Http\Requests\CompteIntegration;

use Illuminate\Foundation\Http\FormRequest;

class ProvisionnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agent_id' => ['required', 'integer', 'exists:agents,id'],
        ];
    }
}
