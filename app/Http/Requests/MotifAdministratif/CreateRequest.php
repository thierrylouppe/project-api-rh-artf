<?php

namespace App\Http\Requests\MotifAdministratif;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nom'         => ['required', 'string', 'max:255', 'unique:motifs_administratifs,nom'],
            'description' => ['nullable', 'string'],
        ];
    }
}
