<?php

namespace App\Http\Requests\Commission;

use Illuminate\Foundation\Http\FormRequest;

/** Ouvrir une commission (préparatoire ou d'avancement). */
class OuvrirRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'date_ouverture' => ['nullable', 'date'],
            'observations'   => ['nullable', 'string', 'max:2000'],
        ];
    }
}
