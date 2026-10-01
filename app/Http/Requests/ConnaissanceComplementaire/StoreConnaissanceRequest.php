<?php

namespace App\Http\Requests\ConnaissanceComplementaire;

use Illuminate\Foundation\Http\FormRequest;

class StoreConnaissanceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'type'        => ['required', 'in:formation,certification,perfectionnement,autre'],
            'domaine'     => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'urgent'      => ['nullable', 'boolean'],
        ];
    }
}
