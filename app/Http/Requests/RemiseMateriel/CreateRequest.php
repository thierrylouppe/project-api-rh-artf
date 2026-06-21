<?php

namespace App\Http\Requests\RemiseMateriel;

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
            'agent_id'       => ['required', 'integer', 'exists:agents,id'],
            'affectation_id' => ['nullable', 'integer', 'exists:affectations,id'],
            'materiel'       => ['required', 'array', 'min:1'],
            'materiel.*'     => ['string'],
            'date_remise'    => ['required', 'date'],
        ];
    }
}
