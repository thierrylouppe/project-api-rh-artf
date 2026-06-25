<?php

namespace App\Http\Requests\Stage;

use Illuminate\Foundation\Http\FormRequest;

class ProlongerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_fin' => ['required', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_fin.required' => 'La nouvelle date de fin est obligatoire.',
            'date_fin.date'     => 'La date de fin doit être une date valide.',
            'date_fin.after'    => 'La nouvelle date de fin doit être dans le futur.',
        ];
    }
}
