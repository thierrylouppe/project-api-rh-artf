<?php

namespace App\Http\Requests\VisiteMedicale;

use App\Enums\TypeVisiteMedicale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'string', Rule::enum(TypeVisiteMedicale::class)],
            'date_visite' => ['sometimes', 'date'],
            'structure_sanitaire_id' => ['sometimes', 'integer', 'exists:structures_sanitaires,id'],
            'observations' => ['nullable', 'string'],
        ];
    }
}
