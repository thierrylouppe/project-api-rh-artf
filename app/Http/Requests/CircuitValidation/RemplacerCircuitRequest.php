<?php

namespace App\Http\Requests\CircuitValidation;

use App\Enums\NiveauValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RemplacerCircuitRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'niveaux'   => ['required', 'array', 'min:1'],
            'niveaux.*' => ['string', Rule::enum(NiveauValidation::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'niveaux.required' => 'La liste des niveaux est obligatoire.',
            'niveaux.min'      => 'Le circuit doit contenir au moins un niveau.',
            'niveaux.*.Illuminate\Validation\Rules\Enum' => 'Un ou plusieurs niveaux fournis sont invalides.',
        ];
    }
}
