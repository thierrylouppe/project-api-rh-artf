<?php

namespace App\Http\Requests\InformationsPersonnelle;

use Illuminate\Foundation\Http\FormRequest;

class UpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'adresse'     => ['nullable', 'string', 'max:255'],
            'quartier'    => ['nullable', 'string', 'max:255'],
            'ville'       => ['nullable', 'string', 'max:255'],
            'code_postal' => ['nullable', 'string', 'max:20'],
            'pays'        => ['nullable', 'string', 'max:100'],
        ];
    }
}
