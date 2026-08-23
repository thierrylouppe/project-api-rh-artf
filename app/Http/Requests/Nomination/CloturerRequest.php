<?php

namespace App\Http\Requests\Nomination;

use Illuminate\Foundation\Http\FormRequest;

class CloturerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_fin' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_fin.before_or_equal' => 'La date de fin ne peut pas être dans le futur.',
        ];
    }
}
