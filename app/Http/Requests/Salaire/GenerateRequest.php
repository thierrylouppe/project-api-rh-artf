<?php

namespace App\Http\Requests\Salaire;

use Illuminate\Foundation\Http\FormRequest;

class GenerateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'valeur_point_indice' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
