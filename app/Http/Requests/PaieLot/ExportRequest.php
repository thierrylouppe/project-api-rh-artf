<?php

namespace App\Http\Requests\PaieLot;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'format' => ['required', 'string', Rule::in(['csv', 'pdf'])],
        ];
    }

    public function messages(): array
    {
        return [
            'format.required' => 'Indiquez le format d\'export (csv ou pdf).',
            'format.in' => 'Le format d\'export doit être csv ou pdf.',
        ];
    }
}
