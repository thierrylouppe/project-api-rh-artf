<?php

namespace App\Http\Requests\AvisHierarchique;

use App\Enums\NiveauAvisHierarchique;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Dépôt ou mise à jour d'un avis hiérarchique (CCN ARTF art. 64).
 */
class PostAvisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'niveau'       => ['required', 'string', Rule::enum(NiveauAvisHierarchique::class)],
            'avis'         => ['nullable', 'string', 'max:2000'],
            'approuve'     => ['nullable', 'boolean'],
            'observations' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'niveau.required' => 'Le niveau hiérarchique est obligatoire.',
            'niveau.in'       => 'Niveau non reconnu. Valeurs acceptées : chef_bureau, chef_service, directeur, directeur_general.',
        ];
    }
}
