<?php

namespace App\Http\Requests\PlanFormation;

use Illuminate\Foundation\Http\FormRequest;

class StoreLigneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'formation_id' => ['required', 'integer', 'exists:catalogue_formations,id'],
            'places_prevues' => ['nullable', 'integer', 'min:1', 'max:500'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
