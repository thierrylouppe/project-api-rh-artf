<?php

namespace App\Http\Requests\Stage;

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
            'note'         => ['required', 'numeric', 'min:0', 'max:20'],
            'appreciation' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required'          => 'La note finale est obligatoire.',
            'note.numeric'           => 'La note doit être un nombre.',
            'note.min'               => 'La note ne peut pas être inférieure à 0.',
            'note.max'               => 'La note ne peut pas dépasser 20.',
            'appreciation.required'  => "L'appréciation est obligatoire.",
            'appreciation.min'       => "L'appréciation doit contenir au moins 10 caractères.",
        ];
    }
}
