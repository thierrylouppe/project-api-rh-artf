<?php

namespace App\Http\Requests\Echelon;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nom'         => ['required', 'string', 'max:255', 'unique:echelons,nom'],
            'numero'      => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
        ];
    }
}
