<?php

namespace App\Http\Requests\Reporting;

use App\Enums\TypeExportReporting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'type' => $this->route('type'),
        ]);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(TypeExportReporting::values())],
            'format' => ['required', 'string', Rule::in(['csv', 'pdf'])],
            'annee' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'portee' => ['nullable', 'string', Rule::in(['annee', 'session'])],
            'session_id' => ['nullable', 'integer', 'exists:session_evaluations,id'],
            'direction_id' => ['nullable', 'integer', 'exists:directions,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'bureau_id' => ['nullable', 'integer', 'exists:bureaus,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Type d\'export inconnu. Valeurs : '.implode(', ', TypeExportReporting::values()).'.',
            'format.required' => 'Indiquez le format d\'export (csv ou pdf).',
            'format.in' => 'Le format d\'export doit être csv ou pdf.',
        ];
    }
}
