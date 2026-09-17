<?php

namespace App\Http\Requests\VisiteMedicale;

use App\Enums\TypeVisiteMedicale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agent_id' => ['required', 'integer', 'exists:agents,id'],
            'type' => ['required', 'string', Rule::enum(TypeVisiteMedicale::class)],
            'date_visite' => ['required', 'date'],
            'structure_sanitaire_id' => ['required', 'integer', 'exists:structures_sanitaires,id'],
            'observations' => ['nullable', 'string'],
        ];
    }
}
