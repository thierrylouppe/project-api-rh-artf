<?php

namespace App\Http\Requests\Commission;

use Illuminate\Foundation\Http\FormRequest;

/** Harmoniser la note d'une fiche en commission préparatoire. */
class NoterFicheRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'evaluation_id'   => ['required', 'integer', 'exists:evaluations,id'],
            'commission_note' => ['required', 'numeric', 'min:0', 'max:20'],
            'note_synthese'   => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'commission_note.required' => 'La note harmonisée est obligatoire.',
            'commission_note.max'      => 'La note ne peut pas dépasser 20.',
        ];
    }
}
