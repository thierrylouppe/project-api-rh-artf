<?php

namespace App\Http\Requests\CatalogueFormation;

use App\Enums\ModaliteFormation;
use App\Enums\TypeActionFormation;
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
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'organisme' => ['nullable', 'string', 'max:255'],
            'lieu' => ['nullable', 'string', 'max:255'],
            'modalite' => ['nullable', 'string', Rule::enum(ModaliteFormation::class)],
            'type_action' => ['required', 'string', Rule::enum(TypeActionFormation::class)],
            'duree_jours' => ['required', 'integer', 'min:1', 'max:2000'],
            'cout' => ['nullable', 'numeric', 'min:0'],
            'anciennete_min_ans' => ['nullable', 'integer', 'min:0', 'max:10'],
            'debit_formation_mois' => ['nullable', 'integer', 'min:1', 'max:60'],
            'actif' => ['nullable', 'boolean'],
        ];
    }
}
