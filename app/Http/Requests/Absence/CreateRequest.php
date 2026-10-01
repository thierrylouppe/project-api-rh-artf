<?php

namespace App\Http\Requests\Absence;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agent_id'        => ['required', 'integer', 'exists:agents,id'],
            'type_absence_id' => ['required', 'integer', 'exists:type_absences,id'],
            'date_debut'      => ['required', 'date'],
            'date_fin'        => ['required', 'date', 'after_or_equal:date_debut'],
            'justifiee'       => ['sometimes', 'boolean'],
            'motif'           => ['nullable', 'string'],
        ];
    }
}
