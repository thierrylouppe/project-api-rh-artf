<?php

namespace App\Http\Requests\CircuitValidation;

use App\Enums\NiveauValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AjouterNiveauRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'niveau' => ['required', 'string', Rule::enum(NiveauValidation::class)],
            'ordre'  => ['required', 'integer', 'min:1'],
        ];
    }
}
