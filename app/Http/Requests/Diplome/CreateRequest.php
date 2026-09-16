<?php

namespace App\Http\Requests\Diplome;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nom'         => ['required', 'string', 'max:255', 'unique:diplomes,nom'],
            'sigle'                    => ['nullable', 'string', 'max:50'],
            'description'              => ['nullable', 'string'],
            'classegrillesalariale_id' => ['nullable', 'integer', 'exists:classegrillesalariales,id'],
            'bonification_echelons'    => ['nullable', 'integer', 'min:0', 'max:12'],
        ];
    }
}
